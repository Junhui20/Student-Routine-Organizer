<?php
require_once '../includes/SessionManager.php';
require_once '../config/database.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
if(!SessionManager::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Check if this is a filter request
if (!isset($_POST['action']) || $_POST['action'] !== 'filter_entries') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get filters from request
    $filters = json_decode($_POST['filters'], true);
    if (!$filters) {
        $filters = [];
    }
    
    // Build the search query
    $query = "SELECT e.*, 
              LENGTH(e.content) as content_length
              FROM diary_entries e 
              WHERE e.user_id = ?";
    
    $params = [SessionManager::getCurrentUserId()];
    $whereConditions = [];
    
    // Search filter
    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $whereConditions[] = "(e.title LIKE ? OR e.content LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Date range filters
    if (!empty($filters['dateFrom'])) {
        $whereConditions[] = "e.entry_date >= ?";
        $params[] = $filters['dateFrom'];
    }
    
    if (!empty($filters['dateTo'])) {
        $whereConditions[] = "e.entry_date <= ?";
        $params[] = $filters['dateTo'];
    }
    
    // Mood filters
    if (!empty($filters['moods']) && is_array($filters['moods'])) {
        $moodPlaceholders = str_repeat('?,', count($filters['moods']) - 1) . '?';
        $whereConditions[] = "e.mood IN ($moodPlaceholders)";
        $params = array_merge($params, $filters['moods']);
    }
    
    // Add WHERE conditions
    if (!empty($whereConditions)) {
        $query .= " AND " . implode(" AND ", $whereConditions);
    }
    
    // Add sorting
    $sortBy = $filters['sortBy'] ?? 'date_desc';
    switch ($sortBy) {
        case 'date_asc':
            $query .= " ORDER BY e.entry_date ASC, e.created_at ASC";
            break;
        case 'title_asc':
            $query .= " ORDER BY e.title ASC";
            break;
        case 'title_desc':
            $query .= " ORDER BY e.title DESC";
            break;
        case 'mood':
            $query .= " ORDER BY e.mood ASC, e.entry_date DESC";
            break;
        case 'length_desc':
            $query .= " ORDER BY content_length DESC";
            break;
        case 'length_asc':
            $query .= " ORDER BY content_length ASC";
            break;
        default: // date_desc
            $query .= " ORDER BY e.entry_date DESC, e.created_at DESC";
            break;
    }
    
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $allEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply content type filters (post-query filtering for complex conditions)
    $filteredEntries = [];
    
    foreach ($allEntries as $entry) {
        $include = true;
        
        // Content type filters
        if (!empty($filters['contentTypes']) && is_array($filters['contentTypes'])) {
            foreach ($filters['contentTypes'] as $contentType) {
                switch ($contentType) {
                    case 'long-entries':
                        // Count words approximately (rough estimation)
                        $wordCount = str_word_count(strip_tags($entry['content']));
                        if ($wordCount < 500) {
                            $include = false;
                        }
                        break;
                    case 'short-entries':
                        // Count words approximately
                        $wordCount = str_word_count(strip_tags($entry['content']));
                        if ($wordCount >= 100) {
                            $include = false;
                        }
                        break;
                }
                
                if (!$include) break;
            }
        }
        
        if ($include) {
            $filteredEntries[] = $entry;
        }
    }
    
    // Prepare response data
    $response = [
        'success' => true,
        'count' => count($filteredEntries),
        'total' => count($allEntries),
        'entries' => $filteredEntries,
        'filters_applied' => $filters
    ];
    
    // Set JSON header
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed. Please try again.',
        'count' => 0,
        'entries' => []
    ]);
}
?> 