<?php

namespace App\Controller;

use App\Service\PlatformService;

class HomeController
{
    public function index(): array
    {
        $service = new PlatformService();
        $data = $service->dashboard();
        $data['dramas'] = $service->frontDramas();
        $data['home_dramas'] = $service->homeDramas(6);
        $previewTemplate = (string) ($_GET['preview_homepage_template'] ?? '');
        if (in_array($previewTemplate, ['mini', 'marketing'], true)) {
            $data['homepage_template'] = $previewTemplate;
            $data['site_config']['homepage_template'] = $previewTemplate;
        }

        return [
            'view' => 'frontend/home',
            'data' => $data,
        ];
    }

    public function promo(string $code = ''): array
    {
        $service = new PlatformService();
        $link = $service->trackPromotionVisit($code);
        $target = $link ? $service->promotionLandingTarget($link) : '/duanju';

        header('Location: ' . $target);
        exit;
    }

    public function landingPage(string $slug = ''): array
    {
        $service = new PlatformService();
        $landingPage = $service->landingPageForVisit($slug);
        if ($landingPage === null) {
            header('Location: /duanju');
            exit;
        }

        return [
            'view' => 'frontend/landing_page',
            'data' => [
                'landing' => $landingPage,
                'user' => $service->currentUser(),
            ],
        ];
    }

    public function landingClick(string $slug = ''): array
    {
        $service = new PlatformService();
        $target = $service->landingPageClickTarget($slug) ?? '/duanju';

        header('Location: ' . $target);
        exit;
    }

    public function duanju(): array
    {
        $service = new PlatformService();

        return [
            'view' => 'frontend/duanju',
            'data' => [
                'user' => $service->currentUser(),
                'dramas' => $service->frontDramas(),
                'hot_dramas' => $service->hotDramas(8),
                'new_dramas' => $service->newDramas(8),
                'categories' => $service->dramaCategories(),
                'watch_history' => $service->watchHistory($service->currentUserId()),
                'followed_dramas' => $service->followedDramas($service->currentUserId()),
            ],
        ];
    }

    public function juchang(): array
    {
        $service = new PlatformService();
        $category = trim((string) ($_GET['category'] ?? '全部'));

        return [
            'view' => 'frontend/juchang',
            'data' => [
                'user' => $service->currentUser(),
                'category' => $category,
                'categories' => $service->dramaCategories(),
                'dramas' => $service->frontDramas($category),
            ],
        ];
    }

    public function novels(): array
    {
        $service = new PlatformService();
        $novels = array_values(array_filter($service->novels(), static fn (array $novel): bool => (string) ($novel['status'] ?? 'online') === 'online'));
        usort($novels, static fn (array $a, array $b): int => [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)] <=> [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)]);
        $siteConfig = $service->siteConfig();
        $novelTemplate = $service->novelHomepageTemplate();
        $previewNovelTemplate = (string) ($_GET['preview_novel_template'] ?? '');
        if (in_array($previewNovelTemplate, ['library', 'ranking'], true)) {
            $novelTemplate = $previewNovelTemplate;
            $siteConfig['novel_homepage_template'] = $previewNovelTemplate;
        }

        return [
            'view' => 'frontend/novels',
            'data' => [
                'user' => $service->currentUser(),
                'novels' => $novels,
                'site_config' => $siteConfig,
                'novel_homepage_template' => $novelTemplate,
            ],
        ];
    }

    public function novel(int $id): array
    {
        $service = new PlatformService();
        $novel = $service->findNovel($id);
        $chapters = array_values((array) ($novel['chapters'] ?? []));
        $firstChapterId = (int) ($chapters[0]['id'] ?? 0);
        $userId = $service->currentUserId();

        return [
            'view' => 'frontend/novel',
            'data' => [
                'user' => $service->currentUser(),
                'novel' => $novel,
                'chapters' => $chapters,
                'chapter_access' => $service->novelChapterAccessMap($id, $userId),
                'first_chapter_id' => $firstChapterId,
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }

    public function novelRead(int $novelId, int $chapterId = 0): array
    {
        $service = new PlatformService();
        $novel = $service->findNovel($novelId);
        $chapters = array_values((array) ($novel['chapters'] ?? []));
        if ($chapterId <= 0 && !empty($chapters)) {
            $chapterId = (int) ($chapters[0]['id'] ?? 0);
        }

        $chapter = null;
        foreach ($chapters as $item) {
            if ((int) ($item['id'] ?? 0) === $chapterId) {
                $chapter = $item;
                break;
            }
        }
        $userId = $service->currentUserId();
        if ($novel && $chapter) {
            $canRead = $service->isNovelChapterFree($novel, $chapter) || $service->hasNovelAccess($userId, $novelId, $chapterId);
            $service->recordContentEvent('view', [
                'content_type' => 'novel',
                'novel_id' => $novelId,
                'chapter_id' => $chapterId,
            ]);
            if (!$canRead) {
                $service->recordContentEvent('lock_exposure', [
                    'content_type' => 'novel',
                    'novel_id' => $novelId,
                    'chapter_id' => $chapterId,
                ]);
            }
        }

        return [
            'view' => 'frontend/novel_read',
            'data' => [
                'user' => $service->currentUser(),
                'novel' => $novel,
                'chapter' => $chapter,
                'chapters' => $chapters,
                'chapter_access' => $service->novelChapterAccessMap($novelId, $userId),
                'can_read' => $novel && $chapter ? ($service->isNovelChapterFree($novel, $chapter) || $service->hasNovelAccess($userId, $novelId, $chapterId)) : false,
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }

    public function yulan(int $dramaId, int $episodeId = 0): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($dramaId);
        $episodes = $service->episodes($dramaId);
        if ($episodeId <= 0 && !empty($episodes)) {
            $episodeId = (int) ($episodes[0]['id'] ?? 0);
        }
        $episode = null;
        foreach ($episodes as $item) {
            if ((int) ($item['id'] ?? 0) === $episodeId) {
                $episode = $item;
                break;
            }
        }
        $userId = $service->currentUserId();
        if ($drama && $episode) {
            $service->recordWatchHistory($dramaId, $episodeId, 0);
            $canWatch = $service->isEpisodeFree($drama, $episode) || $service->hasAccess($userId, $dramaId, $episodeId);
            $service->recordContentEvent('view', [
                'content_type' => 'drama',
                'drama_id' => $dramaId,
                'episode_id' => $episodeId,
            ]);
            if (!$canWatch) {
                $service->recordContentEvent('lock_exposure', [
                    'content_type' => 'drama',
                    'drama_id' => $dramaId,
                    'episode_id' => $episodeId,
                ]);
            }
        }
        $appKey = $service->currentAppKey($_GET);

        return [
            'view' => 'frontend/yulan',
            'data' => [
                'drama' => $drama,
                'episode' => $episode,
                'user' => $service->currentUser(),
                'episodes' => $episodes,
                'episode_access' => $service->episodeAccessMap($dramaId, $userId),
                'can_watch' => $drama && $episode ? ($service->isEpisodeFree($drama, $episode) || $service->hasAccess($userId, $dramaId, $episodeId)) : false,
                'is_followed' => count(array_filter($service->followedDramas($userId), static fn (array $item): bool => (int) ($item['drama_id'] ?? 0) === $dramaId)) > 0,
                'app_key' => $appKey,
                'vip_plans' => $service->vipPlans($appKey),
                'coin_packages' => $service->coinPackages($appKey),
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }

    public function drama(int $id): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($id);
        $userId = $service->currentUserId();
        $episodeAccess = [];
        foreach (($drama['episodes'] ?? []) as $episode) {
            $episodeAccess[(int) $episode['id']] = $drama && ($service->isEpisodeFree($drama, $episode)
                || $service->hasAccess($userId, (int) $drama['id'], (int) $episode['id']));
        }

        return [
            'view' => 'frontend/drama',
            'data' => [
                'drama' => $drama,
                'user' => $service->currentUser(),
                'can_watch' => $drama ? $service->hasAccess($userId, $drama['id']) : false,
                'has_membership' => $service->currentUser()['membership'] ?? false,
                'episode_access' => $episodeAccess,
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }

    public function watch(int $dramaId, int $episodeId): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($dramaId);
        $episodes = $service->episodes($dramaId);
        $userId = $service->currentUserId();
        $episode = null;
        foreach ($episodes as $item) {
            if ((int) $item['id'] === $episodeId) {
                $episode = $item;
                break;
            }
        }

        return [
            'view' => 'frontend/watch',
            'data' => [
                'drama' => $drama,
                'episode' => $episode,
                'user' => $service->currentUser(),
                'can_watch' => $drama && $episode ? ($service->isEpisodeFree($drama, $episode) || $service->hasAccess($userId, $dramaId, $episodeId)) : false,
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }
}
