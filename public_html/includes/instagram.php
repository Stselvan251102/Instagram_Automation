<?php
/**
 * Meta / Instagram Graph API Service
 * Official API Integration for Carousel & Single Image Publishing
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

class InstagramService {
    const API_BASE = 'https://graph.facebook.com';
    const OAUTH_BASE = 'https://www.facebook.com';

    /**
     * Generate Facebook / Instagram OAuth Authorization URL
     */
    public static function getOAuthUrl(?string $state = null): string {
        $appId = INSTAGRAM_APP_ID;
        $redirectUri = INSTAGRAM_REDIRECT_URI;
        $version = INSTAGRAM_GRAPH_VERSION;

        if (empty($state)) {
            $state = bin2hex(random_bytes(16));
            $_SESSION['ig_oauth_state'] = $state;
        }

        $scopes = [
            'instagram_basic',
            'instagram_content_publish',
            'pages_show_list',
            'pages_read_engagement',
            'business_management'
        ];

        $params = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(',', $scopes),
            'response_type' => 'code'
        ]);

        return self::OAUTH_BASE . '/' . $version . '/dialog/oauth?' . $params;
    }

    /**
     * Exchange OAuth Code for User Access Token
     */
    public static function exchangeCodeForToken(string $code): array {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/oauth/access_token';

        $params = [
            'client_id' => INSTAGRAM_APP_ID,
            'client_secret' => INSTAGRAM_APP_SECRET,
            'redirect_uri' => INSTAGRAM_REDIRECT_URI,
            'code' => $code,
        ];

        return self::makeRequest('GET', $url, $params);
    }

    /**
     * Exchange short-lived token for long-lived 60-day token
     */
    public static function getLongLivedToken(string $shortLivedToken): array {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/oauth/access_token';

        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => INSTAGRAM_APP_ID,
            'client_secret' => INSTAGRAM_APP_SECRET,
            'fb_exchange_token' => $shortLivedToken,
        ];

        return self::makeRequest('GET', $url, $params);
    }

    /**
     * Fetch connected Facebook Pages and their associated Instagram Business Accounts
     */
    public static function getConnectedAccounts(string $userAccessToken): array {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/me/accounts';

        $params = [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url,followers_count}',
            'access_token' => $userAccessToken,
        ];

        $response = self::makeRequest('GET', $url, $params);
        $accounts = [];

        if (!empty($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $page) {
                if (!empty($page['instagram_business_account'])) {
                    $ig = $page['instagram_business_account'];
                    $accounts[] = [
                        'page_id' => $page['id'],
                        'page_name' => $page['name'],
                        'page_access_token' => $page['access_token'] ?? $userAccessToken,
                        'instagram_business_id' => $ig['id'],
                        'instagram_username' => $ig['username'] ?? '',
                        'profile_picture_url' => $ig['profile_picture_url'] ?? '',
                        'followers_count' => (int)($ig['followers_count'] ?? 0),
                    ];
                }
            }
        }

        return $accounts;
    }

    /**
     * Create an individual Media Container (Single image or item of a carousel)
     */
    public static function createItemContainer(string $igUserId, string $imageUrl, bool $isCarouselItem, ?string $caption, string $accessToken): string {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/' . $igUserId . '/media';

        $params = [
            'image_url' => $imageUrl,
            'access_token' => $accessToken,
        ];

        if ($isCarouselItem) {
            $params['is_carousel_item'] = 'true';
        } elseif (!empty($caption)) {
            $params['caption'] = $caption;
        }

        $res = self::makeRequest('POST', $url, $params);
        if (empty($res['id'])) {
            $err = $res['error']['message'] ?? 'Failed to create media item container.';
            throw new Exception("Instagram Error: " . $err);
        }
        return $res['id'];
    }

    /**
     * Create Carousel Container grouping multiple item containers
     */
    public static function createCarouselContainer(string $igUserId, array $childrenIds, ?string $caption, string $accessToken): string {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/' . $igUserId . '/media';

        $params = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childrenIds),
            'caption' => $caption ?? '',
            'access_token' => $accessToken,
        ];

        $res = self::makeRequest('POST', $url, $params);
        if (empty($res['id'])) {
            $err = $res['error']['message'] ?? 'Failed to create carousel container.';
            throw new Exception("Instagram Error: " . $err);
        }
        return $res['id'];
    }

    /**
     * Publish Media Container
     */
    public static function publishContainer(string $igUserId, string $creationId, string $accessToken): string {
        $version = INSTAGRAM_GRAPH_VERSION;
        $url = self::API_BASE . '/' . $version . '/' . $igUserId . '/media_publish';

        $params = [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ];

        $res = self::makeRequest('POST', $url, $params);
        if (empty($res['id'])) {
            $err = $res['error']['message'] ?? 'Failed to publish media container.';
            throw new Exception("Instagram Publish Error: " . $err);
        }
        return $res['id'];
    }

    /**
     * Publish complete Multi-Slide Carousel to Instagram
     */
    public static function publishCarousel(string $igUserId, array $imageUrls, ?string $caption, string $accessToken): array {
        if (count($imageUrls) < 2 || count($imageUrls) > 10) {
            throw new Exception("Instagram Carousels require between 2 and 10 images.");
        }

        // Step 1: Create container for each image item
        $childrenIds = [];
        foreach ($imageUrls as $imgUrl) {
            $childrenIds[] = self::createItemContainer($igUserId, $imgUrl, true, null, $accessToken);
            // Brief pause for Instagram ingest
            usleep(250000); // 250ms
        }

        // Step 2: Create master carousel container
        $carouselId = self::createCarouselContainer($igUserId, $childrenIds, $caption, $accessToken);

        // Step 3: Brief wait to ensure Instagram finishes processing media
        sleep(2);

        // Step 4: Publish carousel
        $postId = self::publishContainer($igUserId, $carouselId, $accessToken);

        return [
            'ok' => true,
            'post_id' => $postId,
            'carousel_id' => $carouselId,
            'items_count' => count($childrenIds)
        ];
    }

    /**
     * Publish single image to Instagram
     */
    public static function publishSingleImage(string $igUserId, string $imageUrl, ?string $caption, string $accessToken): array {
        $creationId = self::createItemContainer($igUserId, $imageUrl, false, $caption, $accessToken);
        sleep(1);
        $postId = self::publishContainer($igUserId, $creationId, $accessToken);

        return [
            'ok' => true,
            'post_id' => $postId,
            'creation_id' => $creationId,
        ];
    }

    /**
     * Internal cURL helper
     */
    private static function makeRequest(string $method, string $url, array $params = []): array {
        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Carouselfy-Instagram-Automation/2.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Meta API Connection Failed: " . $curlError);
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $decoded['error']['message'] ?? ("Meta API HTTP " . $httpCode);
            throw new Exception($msg);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
