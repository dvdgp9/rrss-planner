<?php

class WordPressAPI {
    private $site_url;
    private $username;
    private $app_password;
    
    public function __construct($site_url, $username, $app_password) {
        $this->site_url = rtrim($site_url, '/');
        $this->username = $username;
        $this->app_password = $app_password;
    }
    
    /**
     * Test WordPress connection
     */
    public function testConnection() {
        $endpoint = $this->site_url . '/wp-json/wp/v2/users/me';
        
        $response = $this->makeRequest('GET', $endpoint);
        
        if ($response['success']) {
            return [
                'success' => true,
                'user' => $response['data'],
                'message' => 'Conexión exitosa con WordPress'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'],
                'message' => 'Error de conexión con WordPress'
            ];
        }
    }
    
    /**
     * Create or update a WordPress post
     */
    public function publishPost($post_data, $wp_post_id = null) {
        $endpoint = $this->site_url . '/wp-json/wp/v2/posts';
        $method = 'POST';
        
        // If updating existing post
        if ($wp_post_id) {
            $endpoint .= '/' . $wp_post_id;
            $method = 'POST'; // WordPress REST API uses POST for updates too
        }
        
        // Prepare post data for WordPress
        $wp_data = [
            'title' => $post_data['titulo'],
            'content' => $post_data['contenido'],
            'excerpt' => $post_data['excerpt'] ?? '',
            'slug' => $post_data['slug'],
            'status' => $this->mapPostStatus($post_data['estado']),
            'date' => $this->formatDate($post_data['fecha_publicacion'])
        ];
        
        // Handle categories - prioritize WordPress categories if provided  
        if (!empty($post_data['wp_categories'])) {
            // Use WordPress categories by ID directly - ensure integers
            $wp_data['categories'] = array_values(array_map('intval', array_filter($post_data['wp_categories'])));
        } elseif (!empty($post_data['categories'])) {
            // Fallback to creating categories by name
            $wp_data['categories'] = $this->handleCategories($post_data['categories']);
        }
        
        // Handle tags - prioritize WordPress tags if provided
        if (!empty($post_data['wp_tags'])) {
            // Use WordPress tags by ID directly
            $wp_data['tags'] = array_map('intval', $post_data['wp_tags']);
        } elseif (!empty($post_data['tags'])) {
            // Fallback to creating tags by name
            $wp_data['tags'] = $this->handleTags($post_data['tags']);
        }
        
        // Handle featured image
        if (!empty($post_data['imagen_destacada'])) {
            $media_id = $this->uploadFeaturedImage($post_data['imagen_destacada']);
            if ($media_id) {
                $wp_data['featured_media'] = $media_id;
            }
        }
        
        $response = $this->makeRequest($method, $endpoint, $wp_data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'wp_post_id' => $response['data']['id'],
                'wp_url' => $response['data']['link'],
                'message' => 'Post publicado exitosamente en WordPress'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'],
                'message' => 'Error al publicar en WordPress'
            ];
        }
    }
    
    /**
     * Upload featured image to WordPress
     */
    private function uploadFeaturedImage($image_path) {
        if (!file_exists($image_path)) {
            return false;
        }
        
        $endpoint = $this->site_url . '/wp-json/wp/v2/media';
        
        // Get file info
        $file_info = pathinfo($image_path);
        $mime_type = mime_content_type($image_path);
        
        // Prepare headers for file upload
        $headers = [
            'Content-Type: ' . $mime_type,
            'Content-Disposition: attachment; filename="' . $file_info['basename'] . '"',
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->app_password)
        ];
        
        // Upload file
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => file_get_contents($image_path),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            $data = json_decode($response, true);
            return $data['id'] ?? false;
        }
        
        return false;
    }
    
    /**
     * Get all categories from WordPress
     */
    public function getCategories() {
        $endpoint = $this->site_url . '/wp-json/wp/v2/categories?per_page=100&orderby=name&order=asc';
        $response = $this->makeRequest('GET', $endpoint);
        
        if ($response['success']) {
            return [
                'success' => true,
                'categories' => array_map(function($cat) {
                    return [
                        'id' => $cat['id'],
                        'name' => $cat['name'],
                        'slug' => $cat['slug'],
                        'count' => $cat['count']
                    ];
                }, $response['data'])
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'],
                'categories' => []
            ];
        }
    }
    
    /**
     * Get all tags from WordPress
     */
    public function getTags() {
        $endpoint = $this->site_url . '/wp-json/wp/v2/tags?per_page=100&orderby=name&order=asc';
        $response = $this->makeRequest('GET', $endpoint);
        
        if ($response['success']) {
            return [
                'success' => true,
                'tags' => array_map(function($tag) {
                    return [
                        'id' => $tag['id'],
                        'name' => $tag['name'],
                        'slug' => $tag['slug'],
                        'count' => $tag['count']
                    ];
                }, $response['data'])
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['error'],
                'tags' => []
            ];
        }
    }

    /**
     * Handle categories - create if they don't exist
     */
    private function handleCategories($category_names) {
        $category_ids = [];
        
        foreach ($category_names as $category_name) {
            // First, try to find existing category
            $endpoint = $this->site_url . '/wp-json/wp/v2/categories?search=' . urlencode($category_name);
            $response = $this->makeRequest('GET', $endpoint);
            
            if ($response['success'] && !empty($response['data'])) {
                // Category exists
                $category_ids[] = $response['data'][0]['id'];
            } else {
                // Create new category
                $create_endpoint = $this->site_url . '/wp-json/wp/v2/categories';
                $create_response = $this->makeRequest('POST', $create_endpoint, [
                    'name' => $category_name,
                    'slug' => sanitize_title($category_name)
                ]);
                
                if ($create_response['success']) {
                    $category_ids[] = $create_response['data']['id'];
                }
            }
        }
        
        return $category_ids;
    }
    
    /**
     * Handle tags - create if they don't exist
     */
    private function handleTags($tag_names) {
        $tag_ids = [];
        
        foreach ($tag_names as $tag_name) {
            // First, try to find existing tag
            $endpoint = $this->site_url . '/wp-json/wp/v2/tags?search=' . urlencode($tag_name);
            $response = $this->makeRequest('GET', $endpoint);
            
            if ($response['success'] && !empty($response['data'])) {
                // Tag exists
                $tag_ids[] = $response['data'][0]['id'];
            } else {
                // Create new tag
                $create_endpoint = $this->site_url . '/wp-json/wp/v2/tags';
                $create_response = $this->makeRequest('POST', $create_endpoint, [
                    'name' => $tag_name,
                    'slug' => sanitize_title($tag_name)
                ]);
                
                if ($create_response['success']) {
                    $tag_ids[] = $create_response['data']['id'];
                }
            }
        }
        
        return $tag_ids;
    }
    
    /**
     * Map our post status to WordPress status
     */
    private function mapPostStatus($status) {
        switch ($status) {
            case 'draft':
                return 'draft';
            case 'scheduled':
                return 'future';
            case 'publish':
                return 'publish';
            default:
                return 'draft';
        }
    }
    
    /**
     * Format date for WordPress
     */
    private function formatDate($date) {
        return date('c', strtotime($date)); // ISO 8601 format
    }
    
    /**
     * Make HTTP request to WordPress API
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->app_password),
            'Content-Type: application/json'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $decoded_response
            ];
        } else {
            return [
                'success' => false,
                'error' => $decoded_response['message'] ?? 'HTTP Error ' . $http_code,
                'details' => $decoded_response
            ];
        }
    }
}

/**
 * Helper function to sanitize title for slug
 */
function sanitize_title($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

?> 