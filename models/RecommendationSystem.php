<?php
require_once __DIR__ . '/Database.php';

class RecommendationSystem {
    private $db;
    private $k = 5; // Number of neighbors for KNN
    
    public function __construct($k = 5) {
        $this->db = new Database();
        $this->k = $k;
    }
    
    /**
     * K-Nearest Neighbors Recommendation Algorithm
     * Finds similar users based on interaction patterns and recommends courses
     */
    public function getKNNRecommendations($userId, $limit = 5) {
        $conn = $this->db->getConnection();
        
        // Check if user is new (cold start problem)
        if ($this->isNewUser($userId)) {
            return $this->getColdStartRecommendations($limit);
        }
        
        // Get user interaction vector
        $userInteractions = $this->getUserInteractionVector($userId);
        if (empty($userInteractions)) {
            return $this->getColdStartRecommendations($limit);
        }
        
        // Find similar users (KNN)
        $similarUsers = $this->findSimilarUsers($userId, $userInteractions);
        
        if (empty($similarUsers)) {
            return $this->getColdStartRecommendations($limit);
        }
        
        // Get courses enrolled by similar users but not by current user
        $recommendations = $this->generateRecommendationsFromSimilarUsers($userId, $similarUsers, $limit);
        
        // Cache recommendations
        $this->cacheRecommendations($userId, $recommendations, 'knn');
        
        return $recommendations;
    }
    
    /**
     * Check if user is new (cold start detection)
     */
    private function isNewUser($userId) {
        $conn = $this->db->getConnection();
        
        // First check if user_interactions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'user_interactions'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, assume user is new and create table
            $this->createInteractionTable();
            return true;
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as interaction_count 
            FROM user_interactions 
            WHERE user_id = ?
        ");
        
        if ($stmt === false) {
            // If prepare fails, assume user is new
            return true;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return ($result['interaction_count'] ?? 0) < 3;
    }
    
    /**
     * Create user_interactions table if it doesn't exist
     */
    private function createInteractionTable() {
        $conn = $this->db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS `user_interactions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `course_id` int(11) NOT NULL,
            `interaction_type` enum('view','enroll','lesson_complete','quiz_attempt','discussion_post') NOT NULL,
            `interaction_value` decimal(5,2) DEFAULT 1.00,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_course` (`user_id`, `course_id`),
            KEY `idx_interaction_type` (`interaction_type`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->query($sql);
    }
    
    /**
     * Cold start recommendations for new users
     */
    private function getColdStartRecommendations($limit = 5) {
        $conn = $this->db->getConnection();
        
        // Check if enrollments table exists
        $enrollmentsCheck = $conn->query("SHOW TABLES LIKE 'enrollments'");
        $hasEnrollments = $enrollmentsCheck->num_rows > 0;
        
        // Check if quiz_attempts table exists
        $quizCheck = $conn->query("SHOW TABLES LIKE 'quiz_attempts'");
        $hasQuizzes = $quizCheck->num_rows > 0;
        
        // Build query based on available tables
        $sql = "
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name";
        
        if ($hasEnrollments) {
            $sql .= ", COUNT(e.id) as enrollment_count";
        } else {
            $sql .= ", 0 as enrollment_count";
        }
        
        if ($hasQuizzes) {
            $sql .= ", AVG(CASE WHEN qa.passed = 1 THEN 1 ELSE 0 END) as success_rate";
        } else {
            $sql .= ", 0.5 as success_rate";
        }
        
        $sql .= "
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id";
        
        if ($hasEnrollments) {
            $sql .= " LEFT JOIN enrollments_new e ON c.id = e.course_id";
        }
        
        if ($hasQuizzes) {
            $sql .= " LEFT JOIN quiz_attempts qa ON c.id = qa.course_id";
        }
        
        $sql .= "
            WHERE c.status = 'published' 
            AND c.difficulty_level = 'beginner'
            GROUP BY c.id
            ORDER BY ";
        
        if ($hasEnrollments) {
            $sql .= "enrollment_count DESC, ";
        }
        
        if ($hasQuizzes) {
            $sql .= "success_rate DESC, ";
        }
        
        $sql .= "c.created_at DESC
            LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            // Fallback to simple query if complex query fails
            $fallbackSql = "SELECT c.*, cat.name as category_name, u.full_name as instructor_name,
                       0 as enrollment_count, 0.5 as success_rate
                FROM courses_new c
                LEFT JOIN categories_new cat ON c.category_id = cat.id
                LEFT JOIN users_new u ON c.instructor_id = u.id
                WHERE c.status = 'published' 
                AND c.difficulty_level = 'beginner'
                ORDER BY c.created_at DESC
                LIMIT ?";
            
            $stmt = $conn->prepare($fallbackSql);
            if ($stmt === false) {
                return []; // Return empty if everything fails
            }
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $recommendations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Add recommendation scores
        foreach ($recommendations as &$rec) {
            $rec['recommendation_score'] = $this->calculateColdStartScore($rec);
            $rec['recommendation_reason'] = 'Popular foundational course';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate cold start recommendation score
     */
    private function calculateColdStartScore($course) {
        $enrollmentWeight = 0.4;
        $successWeight = 0.3;
        $recencyWeight = 0.3;
        
        $enrollmentScore = min($course['enrollment_count'] / 100, 1.0);
        $successScore = $course['success_rate'] ?? 0.5;
        $recencyScore = $this->calculateRecencyScore($course['created_at']);
        
        return ($enrollmentScore * $enrollmentWeight) + 
               ($successScore * $successWeight) + 
               ($recencyScore * $recencyWeight);
    }
    
    /**
     * Get user interaction vector for similarity calculation
     */
    private function getUserInteractionVector($userId) {
        $conn = $this->db->getConnection();
        
        // Check if user_interactions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'user_interactions'");
        if ($tableCheck->num_rows == 0) {
            return []; // Table doesn't exist, return empty vector
        }
        
        $stmt = $conn->prepare("
            SELECT course_id, 
                   SUM(interaction_value) as total_weight,
                   COUNT(*) as interaction_count
            FROM user_interactions 
            WHERE user_id = ?
            GROUP BY course_id
        ");
        
        if ($stmt === false) {
            return []; // Return empty if prepare fails
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $vector = [];
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($results as $row) {
            $vector[$row['course_id']] = [
                'weight' => $row['total_weight'],
                'count' => $row['interaction_count']
            ];
        }
        
        return $vector;
    }
    
    /**
     * Find similar users using cosine similarity (KNN algorithm)
     */
    private function findSimilarUsers($userId, $userInteractions) {
        $conn = $this->db->getConnection();
        
        // Get all other users with interactions
        $stmt = $conn->prepare("
            SELECT DISTINCT user_id 
            FROM user_interactions 
            WHERE user_id != ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $otherUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $similarities = [];
        
        foreach ($otherUsers as $otherUser) {
            $otherUserId = $otherUser['user_id'];
            $otherInteractions = $this->getUserInteractionVector($otherUserId);
            
            $similarity = $this->calculateCosineSimilarity($userInteractions, $otherInteractions);
            
            if ($similarity > 0) {
                $similarities[] = [
                    'user_id' => $otherUserId,
                    'similarity' => $similarity
                ];
            }
        }
        
        // Sort by similarity and take top k
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similarities, 0, $this->k);
    }
    
    /**
     * Calculate cosine similarity between two user interaction vectors
     */
    private function calculateCosineSimilarity($vector1, $vector2) {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        // Get all course IDs from both vectors
        $allCourses = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        foreach ($allCourses as $courseId) {
            $weight1 = $vector1[$courseId]['weight'] ?? 0;
            $weight2 = $vector2[$courseId]['weight'] ?? 0;
            
            $dotProduct += $weight1 * $weight2;
            $magnitude1 += $weight1 * $weight1;
            $magnitude2 += $weight2 * $weight2;
        }
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($magnitude1) * sqrt($magnitude2));
    }
    
    /**
     * Generate recommendations from similar users
     */
    private function generateRecommendationsFromSimilarUsers($userId, $similarUsers, $limit) {
        $conn = $this->db->getConnection();
        
        // Get courses enrolled by similar users
        $similarUserIds = array_column($similarUsers, 'user_id');
        $placeholders = str_repeat('?,', count($similarUserIds) - 1) . '?';
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name,
                   COUNT(e.id) as enrollment_count,
                   SUM(s.similarity) as similarity_sum
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            JOIN enrollments_new e ON c.id = e.course_id
            JOIN (
                SELECT ?, user_id, similarity as similarity FROM user_similarity_cache WHERE user_id_1 = ?
                UNION ALL
                SELECT ?, user_id, similarity as similarity FROM user_similarity_cache WHERE user_id_2 = ?
            ) s ON e.student_id = s.user_id
            WHERE c.status = 'published'
            AND e.student_id IN ($placeholders)
            AND c.id NOT IN (SELECT course_id FROM enrollments_new WHERE student_id = ?)
            GROUP BY c.id
            ORDER BY similarity_sum DESC, enrollment_count DESC
            LIMIT ?
        ");
        
        $params = array_merge([$userId, $userId, $userId, $userId], $similarUserIds, [$userId, $limit]);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $recommendations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate recommendation scores
        foreach ($recommendations as &$rec) {
            $rec['recommendation_score'] = $this->calculateRecommendationScore($rec, $similarUsers);
            $rec['recommendation_reason'] = 'Students like you also took this course';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate final recommendation score
     */
    private function calculateRecommendationScore($course, $similarUsers) {
        $similarityWeight = 0.6;
        $popularityWeight = 0.2;
        $recencyWeight = 0.2;
        
        $similarityScore = min($course['similarity_sum'] / count($similarUsers), 1.0);
        $popularityScore = min($course['enrollment_count'] / 50, 1.0);
        $recencyScore = $this->calculateRecencyScore($course['created_at']);
        
        return ($similarityScore * $similarityWeight) + 
               ($popularityScore * $popularityWeight) + 
               ($recencyScore * $recencyWeight);
    }
    
    /**
     * Calculate recency score (newer courses get higher scores)
     */
    private function calculateRecencyScore($createdAt) {
        $daysSinceCreation = (time() - strtotime($createdAt)) / (60 * 60 * 24);
        return max(0, 1 - ($daysSinceCreation / 365)); // Decay over 1 year
    }
    
    /**
     * Log user interaction for learning
     */
    public function logInteraction($userId, $courseId, $interactionType, $value = 1.0) {
        $conn = $this->db->getConnection();
        
        // Ensure table exists
        $this->createInteractionTable();
        
        $stmt = $conn->prepare("
            INSERT INTO user_interactions (user_id, course_id, interaction_type, interaction_value)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt === false) {
            return false; // Return false if prepare fails
        }
        
        $stmt->bind_param("iisd", $userId, $courseId, $interactionType, $value);
        
        if ($stmt->execute()) {
            // Update similarity cache for this user
            $this->updateSimilarityCache($userId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update similarity cache for a user
     */
    private function updateSimilarityCache($userId) {
        $conn = $this->db->getConnection();
        
        // Ensure similarity cache table exists
        $this->createSimilarityCacheTable();
        
        $userInteractions = $this->getUserInteractionVector($userId);
        
        if (empty($userInteractions)) {
            return; // Skip if user has no interactions
        }
        
        // Get all other users
        $stmt = $conn->prepare("SELECT DISTINCT user_id FROM user_interactions WHERE user_id != ?");
        if ($stmt === false) {
            return; // Skip if table doesn't exist
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $otherUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($otherUsers as $otherUser) {
            $otherUserId = $otherUser['user_id'];
            $otherInteractions = $this->getUserInteractionVector($otherUserId);
            
            $similarity = $this->calculateCosineSimilarity($userInteractions, $otherInteractions);
            
            if ($similarity > 0) {
                // Update cache
                $stmt = $conn->prepare("
                    INSERT INTO user_similarity_cache (user_id_1, user_id_2, similarity_score)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE similarity_score = ?, calculated_at = NOW()
                ");
                
                if ($stmt !== false) {
                    $stmt->bind_param("iidd", $userId, $otherUserId, $similarity, $similarity);
                    $stmt->execute();
                }
            }
        }
    }
    
    /**
     * Create user_similarity_cache table if it doesn't exist
     */
    private function createSimilarityCacheTable() {
        $conn = $this->db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS `user_similarity_cache` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id_1` int(11) NOT NULL,
            `user_id_2` int(11) NOT NULL,
            `similarity_score` decimal(5,4) DEFAULT 0.0000,
            `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_pair` (`user_id_1`, `user_id_2`),
            KEY `idx_user_1` (`user_id_1`),
            KEY `idx_user_2` (`user_id_2`),
            KEY `idx_similarity` (`similarity_score`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->query($sql);
    }
    
    /**
     * Cache recommendations for faster retrieval
     */
    private function cacheRecommendations($userId, $recommendations, $type) {
        $conn = $this->db->getConnection();
        
        // Ensure table exists
        $this->createRecommendationsTable();
        
        // Clear old recommendations
        $stmt = $conn->prepare("DELETE FROM course_recommendations WHERE user_id = ? AND recommendation_type = ?");
        if ($stmt === false) {
            return; // Skip caching if table operations fail
        }
        $stmt->bind_param("is", $userId, $type);
        $stmt->execute();
        
        // Insert new recommendations
        foreach ($recommendations as $rec) {
            $stmt = $conn->prepare("
                INSERT INTO course_recommendations (user_id, course_id, recommendation_score, recommendation_type, expires_at)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
            
            if ($stmt !== false) {
                $stmt->bind_param("idis", $userId, $rec['id'], $rec['recommendation_score'], $type);
                $stmt->execute();
            }
        }
    }
    
    /**
     * Get cached recommendations if available
     */
    public function getCachedRecommendations($userId, $type = 'knn') {
        $conn = $this->db->getConnection();
        
        // Check if course_recommendations table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'course_recommendations'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, create it
            $this->createRecommendationsTable();
            return []; // Return empty for now
        }
        
        $stmt = $conn->prepare("
            SELECT cr.*, c.*, cat.name as category_name, u.full_name as instructor_name
            FROM course_recommendations cr
            JOIN courses_new c ON cr.course_id = c.id
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            LEFT JOIN users_new u ON c.instructor_id = u.id
            WHERE cr.user_id = ? 
            AND cr.recommendation_type = ?
            AND (cr.expires_at IS NULL OR cr.expires_at > NOW())
            ORDER BY cr.recommendation_score DESC
        ");
        
        if ($stmt === false) {
            return []; // Return empty if prepare fails
        }
        
        $stmt->bind_param("is", $userId, $type);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create course_recommendations table if it doesn't exist
     */
    private function createRecommendationsTable() {
        $conn = $this->db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS `course_recommendations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `course_id` int(11) NOT NULL,
            `recommendation_type` enum('knn','cold_start','popular','collaborative') NOT NULL DEFAULT 'knn',
            `recommendation_score` decimal(5,4) DEFAULT 0.0000,
            `recommendation_reason` text,
            `expires_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_course_type` (`user_id`, `course_id`, `recommendation_type`),
            KEY `idx_user_recommendations` (`user_id`, `recommendation_type`),
            KEY `idx_course_recommendations` (`course_id`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->query($sql);
    }
    
    /**
     * Get personalized learning path using dynamic programming
     */
    public function getPersonalizedLearningPath($userId, $targetSkills = []) {
        $conn = $this->db->getConnection();
        
        // Get user's current progress and skills
        $userProfile = $this->buildUserProfile($userId);
        
        // Get available courses
        $availableCourses = $this->getAvailableCoursesForPath($userId);
        
        // Use dynamic programming to find optimal path
        $optimalPath = $this->calculateOptimalLearningPath($userProfile, $availableCourses, $targetSkills);
        
        // Save the learning path
        $this->saveLearningPath($userId, $optimalPath);
        
        return $optimalPath;
    }
    
    /**
     * Build comprehensive user profile
     */
    private function buildUserProfile($userId) {
        $conn = $this->db->getConnection();
        
        $profile = [
            'user_id' => $userId,
            'completed_courses' => [],
            'current_skills' => [],
            'learning_style' => 'balanced',
            'difficulty_preference' => 'progressive',
            'time_availability' => 10 // hours per week
        ];
        
        // Get completed courses
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name
            FROM enrollments_new e
            JOIN courses_new c ON e.course_id = c.id
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            WHERE e.student_id = ? AND e.status = 'completed'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $profile['completed_courses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Analyze skills from completed courses
        foreach ($profile['completed_courses'] as $course) {
            $profile['current_skills'][] = $course['category_name'];
        }
        
        return $profile;
    }
    
    /**
     * Get courses available for learning path
     */
    private function getAvailableCoursesForPath($userId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT c.*, cat.name as category_name
            FROM courses_new c
            LEFT JOIN categories_new cat ON c.category_id = cat.id
            WHERE c.status = 'published'
            AND c.id NOT IN (SELECT course_id FROM enrollments_new WHERE student_id = ?)
            ORDER BY c.difficulty_level, c.created_at
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Calculate optimal learning path using dynamic programming
     */
    private function calculateOptimalLearningPath($userProfile, $availableCourses, $targetSkills) {
        // This is a simplified DP approach for course sequencing
        
        $path = [];
        $remainingCourses = $availableCourses;
        $currentSkills = $userProfile['current_skills'];
        
        // Sort courses by difficulty and prerequisites
        usort($remainingCourses, function($a, $b) {
            $difficultyOrder = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3];
            return $difficultyOrder[$a['difficulty_level']] <=> $difficultyOrder[$b['difficulty_level']];
        });
        
        // Build path progressively
        foreach ($remainingCourses as $course) {
            if ($this->shouldIncludeCourse($course, $currentSkills, $targetSkills)) {
                $path[] = [
                    'course' => $course,
                    'position' => count($path) + 1,
                    'estimated_duration' => $course['duration_hours'] ?? 20,
                    'prerequisites_met' => $this->checkPrerequisites($course, $currentSkills)
                ];
                $currentSkills[] = $course['category_name'];
                
                if (count($path) >= 10) break; // Limit path length
            }
        }
        
        return [
            'path_name' => 'Personalized Learning Path',
            'course_sequence' => $path,
            'estimated_duration' => array_sum(array_column($path, 'estimated_duration')),
            'difficulty_progression' => 'adaptive'
        ];
    }
    
    /**
     * Check if course should be included in path
     */
    private function shouldIncludeCourse($course, $currentSkills, $targetSkills) {
        // Include if matches target skills or builds foundational knowledge
        if (in_array($course['category_name'], $targetSkills)) {
            return true;
        }
        
        // Include foundational courses
        if ($course['difficulty_level'] === 'beginner' && empty($currentSkills)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if prerequisites are met
     */
    private function checkPrerequisites($course, $currentSkills) {
        // Simplified prerequisite checking
        if ($course['difficulty_level'] === 'beginner') {
            return true;
        }
        
        // Check if user has completed courses in same category
        return in_array($course['category_name'], $currentSkills);
    }
    
    /**
     * Save learning path to database
     */
    private function saveLearningPath($userId, $path) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO learning_paths (user_id, path_name, course_sequence, estimated_duration, difficulty_progression)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $sequenceJson = json_encode($path['course_sequence']);
        $stmt->bind_param("issis", 
            $userId, 
            $path['path_name'], 
            $sequenceJson, 
            $path['estimated_duration'], 
            $path['difficulty_progression']
        );
        
        return $stmt->execute();
    }
}
?>
