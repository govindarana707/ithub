<?php
require_once __DIR__ . '/Database.php';

class ProgressTracking {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Dynamic Programming Algorithm for Progress Tracking
     * Calculates optimal learning paths and completion probabilities
     */
    public function calculateLearningProgress($userId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Get course structure and user data
        $courseStructure = $this->getCourseStructure($courseId);
        $userProgress = $this->getUserProgressData($userId, $courseId);
        
        // Apply dynamic programming to calculate optimal progress
        $progressState = $this->applyDynamicProgramming($courseStructure, $userProgress);
        
        // Calculate metrics
        $completionPercentage = $this->calculateCompletionPercentage($progressState);
        $completionProbability = $this->estimateCompletionProbability($progressState);
        $estimatedCompletionTime = $this->estimateCompletionTime($progressState);
        $optimalPathScore = $this->calculateOptimalPathScore($progressState);
        
        // Update progress tracking table
        $this->updateProgressTracking($userId, $courseId, $progressState, $optimalPathScore, $completionProbability, $estimatedCompletionTime);
        
        return [
            'completion_percentage' => $completionPercentage,
            'completion_probability' => $completionProbability,
            'estimated_completion_time' => $estimatedCompletionTime,
            'optimal_path_score' => $optimalPathScore,
            'progress_state' => $progressState,
            'next_steps' => $this->generateNextSteps($progressState),
            'alerts' => $this->generateProgressAlerts($progressState)
        ];
    }
    
    /**
     * Get course structure for DP calculation
     */
    private function getCourseStructure($courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT l.*, 
                   (SELECT COUNT(*) FROM quiz_attempts qa 
                    JOIN quizzes q ON qa.quiz_id = q.id 
                    WHERE q.lesson_id = l.id AND qa.student_id = ? AND qa.passed = 1) as passed_quizzes,
                   (SELECT COUNT(*) FROM quizzes q WHERE q.lesson_id = l.id) as total_quizzes
            FROM lessons l
            WHERE l.course_id = ?
            ORDER BY l.lesson_order
        ");
        
        if ($stmt === false) {
            // Return basic structure if lessons table doesn't exist or query fails
            return [
                'lessons' => [],
                'total_lessons' => 0,
                'dependency_graph' => []
            ];
        }
        
        // We need both user_id and course_id, but we don't have user_id here
        // Let's modify to not include user-specific data in structure
        $stmt = $conn->prepare("
            SELECT l.*, 
                   (SELECT COUNT(*) FROM quizzes q WHERE q.lesson_id = l.id) as total_quizzes
            FROM lessons l
            WHERE l.course_id = ?
            ORDER BY l.lesson_order
        ");
        
        if ($stmt === false) {
            return [
                'lessons' => [],
                'total_lessons' => 0,
                'dependency_graph' => []
            ];
        }
        
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Build dependency graph
        $structure = [
            'lessons' => $lessons,
            'total_lessons' => count($lessons),
            'dependency_graph' => $this->buildDependencyGraph($lessons)
        ];
        
        return $structure;
    }
    
    /**
     * Build lesson dependency graph
     */
    private function buildDependencyGraph($lessons) {
        $graph = [];
        
        foreach ($lessons as $lesson) {
            $lessonId = $lesson['id'];
            $graph[$lessonId] = [];
            
            // Add dependencies based on lesson order
            foreach ($lessons as $otherLesson) {
                if ($otherLesson['lesson_order'] < $lesson['lesson_order']) {
                    $graph[$lessonId][] = $otherLesson['id'];
                }
            }
        }
        
        return $graph;
    }
    
    /**
     * Get user's current progress data
     */
    private function getUserProgressData($userId, $courseId) {
        $conn = $this->db->getConnection();
        
        $progress = [
            'completed_lessons' => [],
            'quiz_scores' => [],
            'time_spent' => [],
            'interaction_frequency' => []
        ];
        
        // Get completed lessons - check if table exists first
        $completedLessonsCheck = $conn->query("SHOW TABLES LIKE 'completed_lessons'");
        if ($completedLessonsCheck->num_rows > 0) {
            $lessonsCheck = $conn->query("SHOW TABLES LIKE 'lessons'");
            if ($lessonsCheck->num_rows > 0) {
                $stmt = $conn->prepare("
                    SELECT cl.*, l.lesson_order, l.duration_minutes
                    FROM completed_lessons cl
                    JOIN lessons l ON cl.lesson_id = l.id
                    WHERE cl.student_id = ? AND l.course_id = ?
                ");
                
                if ($stmt !== false) {
                    $stmt->bind_param("ii", $userId, $courseId);
                    $stmt->execute();
                    $progress['completed_lessons'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                }
            }
        }
        
        // Get quiz performance - check if tables exist
        $quizAttemptsCheck = $conn->query("SHOW TABLES LIKE 'quiz_attempts'");
        $quizzesCheck = $conn->query("SHOW TABLES LIKE 'quizzes'");
        
        if ($quizAttemptsCheck->num_rows > 0 && $quizzesCheck->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT qa.*, q.lesson_id, q.title as quiz_title
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                WHERE qa.student_id = ? AND q.course_id = ?
                ORDER BY qa.completed_at
            ");
            
            if ($stmt !== false) {
                $stmt->bind_param("ii", $userId, $courseId);
                $stmt->execute();
                $progress['quiz_scores'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        // Calculate interaction patterns
        $progress['interaction_frequency'] = $this->calculateInteractionFrequency($userId, $courseId);
        
        return $progress;
    }
    
    /**
     * Calculate user interaction frequency
     */
    private function calculateInteractionFrequency($userId, $courseId) {
        $conn = $this->db->getConnection();
        
        // Check if user_interactions table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'user_interactions'");
        if ($tableCheck->num_rows == 0) {
            return []; // Table doesn't exist, return empty array
        }
        
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as interactions,
                SUM(interaction_value) as total_value
            FROM user_interactions
            WHERE user_id = ? AND course_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        if ($stmt === false) {
            return []; // Return empty if prepare fails
        }
        
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Apply Dynamic Programming algorithm to calculate optimal progress
     */
    private function applyDynamicProgramming($courseStructure, $userProgress) {
        $lessons = $courseStructure['lessons'];
        $completedLessons = array_column($userProgress['completed_lessons'], 'lesson_id');
        $quizScores = $userProgress['quiz_scores'];
        
        // Initialize DP table
        $dpTable = [];
        $optimalPaths = [];
        
        foreach ($lessons as $lesson) {
            $lessonId = $lesson['id'];
            $dpTable[$lessonId] = [];
            $optimalPaths[$lessonId] = [];
            
            // Calculate states for this lesson
            $states = $this->calculateLessonStates($lesson, $completedLessons, $quizScores);
            
            foreach ($states as $state => $value) {
                // Get prerequisites
                $prerequisites = $courseStructure['dependency_graph'][$lessonId] ?? [];
                
                // Find best path to this state
                $bestValue = $value;
                $bestPath = [$lessonId => $state];
                
                foreach ($prerequisites as $prereqId) {
                    if (isset($dpTable[$prereqId])) {
                        foreach ($dpTable[$prereqId] as $prereqState => $prereqValue) {
                            $transitionValue = $this->calculateTransitionValue($prereqState, $state, $prereqId, $lessonId);
                            $totalValue = $prereqValue + $value + $transitionValue;
                            
                            if ($totalValue > $bestValue) {
                                $bestValue = $totalValue;
                                $bestPath = array_merge($optimalPaths[$prereqId][$prereqState] ?? [], [$lessonId => $state]);
                            }
                        }
                    }
                }
                
                $dpTable[$lessonId][$state] = $bestValue;
                $optimalPaths[$lessonId][$state] = $bestPath;
            }
        }
        
        // Find optimal final state
        $finalState = $this->findOptimalFinalState($dpTable, $optimalPaths);
        
        return [
            'dp_table' => $dpTable,
            'optimal_paths' => $optimalPaths,
            'current_state' => $this->getCurrentState($lessons, $completedLessons, $quizScores),
            'optimal_final_state' => $finalState,
            'learning_velocity' => $this->calculateLearningVelocity($userProgress),
            'engagement_score' => $this->calculateEngagementScore($userProgress)
        ];
    }
    
    /**
     * Calculate possible states for a lesson
     */
    private function calculateLessonStates($lesson, $completedLessons, $quizScores) {
        $states = [];
        $lessonId = $lesson['id'];
        
        // Check if lesson is completed
        $isCompleted = in_array($lessonId, $completedLessons);
        
        // Get quiz performance for this lesson
        $lessonQuizScores = array_filter($quizScores, function($quiz) use ($lessonId) {
            return $quiz['lesson_id'] == $lessonId;
        });
        
        $avgQuizScore = 0;
        if (!empty($lessonQuizScores)) {
            $avgQuizScore = array_sum(array_column($lessonQuizScores, 'percentage')) / count($lessonQuizScores);
        }
        
        // Define states based on completion and performance
        if ($isCompleted) {
            if ($avgQuizScore >= 80) {
                $states['mastered'] = 100;
            } elseif ($avgQuizScore >= 60) {
                $states['completed'] = 75;
            } else {
                $states['completed_weak'] = 50;
            }
        } else {
            $states['not_started'] = 0;
            $states['in_progress'] = 25;
        }
        
        return $states;
    }
    
    /**
     * Calculate transition value between states
     */
    private function calculateTransitionValue($fromState, $toState, $fromLesson, $toLesson) {
        // Base transition value
        $value = 0;
        
        // Reward for progression
        if ($fromState !== 'not_started' && $toState !== 'not_started') {
            $value += 10;
        }
        
        // Reward for mastery
        if ($toState === 'mastered') {
            $value += 20;
        }
        
        // Penalize for weak completion
        if ($toState === 'completed_weak') {
            $value -= 5;
        }
        
        // Smooth progression bonus
        $progressionBonus = $this->calculateProgressionBonus($fromState, $toState);
        $value += $progressionBonus;
        
        return $value;
    }
    
    /**
     * Calculate progression bonus
     */
    private function calculateProgressionBonus($fromState, $toState) {
        $stateOrder = [
            'not_started' => 0,
            'in_progress' => 1,
            'completed_weak' => 2,
            'completed' => 3,
            'mastered' => 4
        ];
        
        $fromOrder = $stateOrder[$fromState] ?? 0;
        $toOrder = $stateOrder[$toState] ?? 0;
        
        if ($toOrder > $fromOrder) {
            return ($toOrder - $fromOrder) * 5;
        }
        
        return 0;
    }
    
    /**
     * Find optimal final state
     */
    private function findOptimalFinalState($dpTable, $optimalPaths) {
        $maxValue = 0;
        $optimalState = null;
        $optimalPath = [];
        
        foreach ($dpTable as $lessonId => $states) {
            foreach ($states as $state => $value) {
                if ($value > $maxValue) {
                    $maxValue = $value;
                    $optimalState = ['lesson_id' => $lessonId, 'state' => $state];
                    $optimalPath = $optimalPaths[$lessonId][$state] ?? [];
                }
            }
        }
        
        return [
            'state' => $optimalState,
            'path' => $optimalPath,
            'total_value' => $maxValue
        ];
    }
    
    /**
     * Get current user state
     */
    private function getCurrentState($lessons, $completedLessons, $quizScores) {
        $currentState = [];
        
        foreach ($lessons as $lesson) {
            $lessonId = $lesson['id'];
            $isCompleted = in_array($lessonId, $completedLessons);
            
            $lessonQuizScores = array_filter($quizScores, function($quiz) use ($lessonId) {
                return $quiz['lesson_id'] == $lessonId;
            });
            
            $avgQuizScore = 0;
            if (!empty($lessonQuizScores)) {
                $avgQuizScore = array_sum(array_column($lessonQuizScores, 'percentage')) / count($lessonQuizScores);
            }
            
            if ($isCompleted) {
                if ($avgQuizScore >= 80) {
                    $currentState[$lessonId] = 'mastered';
                } elseif ($avgQuizScore >= 60) {
                    $currentState[$lessonId] = 'completed';
                } else {
                    $currentState[$lessonId] = 'completed_weak';
                }
            } else {
                $currentState[$lessonId] = 'not_started';
            }
        }
        
        return $currentState;
    }
    
    /**
     * Calculate learning velocity
     */
    private function calculateLearningVelocity($userProgress) {
        $completedLessons = $userProgress['completed_lessons'];
        $interactionFreq = $userProgress['interaction_frequency'];
        
        if (empty($completedLessons) || empty($interactionFreq)) {
            return 0;
        }
        
        // Calculate lessons per week
        $timeSpan = max(1, count($interactionFreq));
        $lessonsPerWeek = count($completedLessons) / ($timeSpan / 7);
        
        return min($lessonsPerWeek, 10); // Cap at 10 lessons per week
    }
    
    /**
     * Calculate engagement score
     */
    private function calculateEngagementScore($userProgress) {
        $interactionFreq = $userProgress['interaction_frequency'];
        $quizScores = $userProgress['quiz_scores'];
        
        if (empty($interactionFreq)) {
            return 0;
        }
        
        // Frequency score (0-40 points)
        $avgInteractions = array_sum(array_column($interactionFreq, 'interactions')) / count($interactionFreq);
        $frequencyScore = min($avgInteractions / 5, 1) * 40;
        
        // Quiz performance score (0-30 points)
        $quizScore = 0;
        if (!empty($quizScores)) {
            $avgQuizScore = array_sum(array_column($quizScores, 'percentage')) / count($quizScores);
            $quizScore = ($avgQuizScore / 100) * 30;
        }
        
        // Consistency score (0-30 points)
        $consistencyScore = $this->calculateConsistencyScore($interactionFreq);
        
        return $frequencyScore + $quizScore + $consistencyScore;
    }
    
    /**
     * Calculate consistency score
     */
    private function calculateConsistencyScore($interactionFreq) {
        if (count($interactionFreq) < 7) {
            return 0;
        }
        
        $interactions = array_column($interactionFreq, 'interactions');
        $avgInteractions = array_sum($interactions) / count($interactions);
        
        if ($avgInteractions == 0) {
            return 0;
        }
        
        $variance = 0;
        foreach ($interactions as $interaction) {
            $variance += pow($interaction - $avgInteractions, 2);
        }
        $variance /= count($interactions);
        
        $consistency = max(0, 1 - ($variance / ($avgInteractions * $avgInteractions)));
        
        return $consistency * 30;
    }
    
    /**
     * Calculate completion percentage
     */
    private function calculateCompletionPercentage($progressState) {
        $currentState = $progressState['current_state'];
        $totalLessons = count($currentState);
        
        if ($totalLessons == 0) {
            return 0;
        }
        
        $completedWeight = 0;
        foreach ($currentState as $lessonId => $state) {
            switch ($state) {
                case 'mastered':
                    $completedWeight += 100;
                    break;
                case 'completed':
                    $completedWeight += 80;
                    break;
                case 'completed_weak':
                    $completedWeight += 60;
                    break;
                case 'in_progress':
                    $completedWeight += 30;
                    break;
                case 'not_started':
                    $completedWeight += 0;
                    break;
            }
        }
        
        return round($completedWeight / $totalLessons, 2);
    }
    
    /**
     * Estimate completion probability using DP results
     */
    private function estimateCompletionProbability($progressState) {
        $learningVelocity = $progressState['learning_velocity'];
        $engagementScore = $progressState['engagement_score'];
        $optimalValue = $progressState['optimal_final_state']['total_value'] ?? 0;
        
        // Base probability from engagement
        $baseProbability = $engagementScore / 100;
        
        // Adjust based on learning velocity
        $velocityFactor = min($learningVelocity / 3, 1); // 3 lessons per week is ideal
        $velocityBonus = $velocityFactor * 0.2;
        
        // Adjust based on optimal path achievement
        $optimalFactor = min($optimalValue / 500, 1); // 500 is max possible value
        $optimalBonus = $optimalFactor * 0.1;
        
        $probability = $baseProbability + $velocityBonus + $optimalBonus;
        
        return min(max($probability, 0), 1);
    }
    
    /**
     * Estimate completion time in days
     */
    private function estimateCompletionTime($progressState) {
        $learningVelocity = $progressState['learning_velocity'];
        $completionPercentage = $this->calculateCompletionPercentage($progressState);
        $remainingPercentage = 100 - $completionPercentage;
        
        if ($learningVelocity <= 0) {
            return -1; // Cannot estimate
        }
        
        // Estimate lessons per week
        $lessonsPerWeek = $learningVelocity;
        
        // Convert remaining percentage to estimated lessons
        $totalLessons = count($progressState['current_state']);
        $remainingLessons = ($remainingPercentage / 100) * $totalLessons;
        
        // Calculate days needed
        $weeksNeeded = $remainingLessons / $lessonsPerWeek;
        $daysNeeded = $weeksNeeded * 7;
        
        // Add buffer for unexpected delays
        $daysNeeded *= 1.2;
        
        return round($daysNeeded);
    }
    
    /**
     * Calculate optimal path score
     */
    private function calculateOptimalPathScore($progressState) {
        return $progressState['optimal_final_state']['total_value'] ?? 0;
    }
    
    /**
     * Generate next steps for user
     */
    private function generateNextSteps($progressState) {
        $nextSteps = [];
        $currentState = $progressState['current_state'];
        $optimalPath = $progressState['optimal_final_state']['path'] ?? [];
        
        // Find next lesson to work on
        foreach ($currentState as $lessonId => $state) {
            if ($state === 'not_started' || $state === 'in_progress') {
                $nextSteps[] = [
                    'type' => 'lesson',
                    'lesson_id' => $lessonId,
                    'action' => $state === 'not_started' ? 'Start lesson' : 'Continue lesson',
                    'priority' => 'high'
                ];
                break;
            }
        }
        
        // Add recommendations based on optimal path
        foreach ($optimalPath as $lessonId => $optimalState) {
            $currentState = $progressState['current_state'][$lessonId] ?? 'not_started';
            
            if ($currentState !== $optimalState) {
                $nextSteps[] = [
                    'type' => 'improvement',
                    'lesson_id' => $lessonId,
                    'action' => "Improve to {$optimalState}",
                    'priority' => 'medium'
                ];
            }
        }
        
        return $nextSteps;
    }
    
    /**
     * Generate progress alerts
     */
    private function generateProgressAlerts($progressState) {
        $alerts = [];
        $learningVelocity = $progressState['learning_velocity'];
        $engagementScore = $progressState['engagement_score'];
        $completionProbability = $this->estimateCompletionProbability($progressState);
        
        // Low velocity alert
        if ($learningVelocity < 1) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Your learning pace is slower than recommended. Consider increasing study time.',
                'action' => 'Create study schedule'
            ];
        }
        
        // Low engagement alert
        if ($engagementScore < 30) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Low engagement detected. Try interactive content or join discussions.',
                'action' => 'Explore interactive features'
            ];
        }
        
        // Low completion probability
        if ($completionProbability < 0.5) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Risk of not completing course. Consider additional support.',
                'action' => 'Contact instructor or study group'
            ];
        }
        
        // Achievement alerts
        if ($engagementScore > 80) {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Excellent engagement! Keep up the great work.',
                'action' => 'Share achievement'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Update progress tracking in database
     */
    private function updateProgressTracking($userId, $courseId, $progressState, $optimalPathScore, $completionProbability, $estimatedCompletionTime) {
        $conn = $this->db->getConnection();
        
        // Check if learning_progress_dp table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'learning_progress_dp'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, create it
            $this->createProgressTrackingTable();
        }
        
        $progressJson = json_encode($progressState);
        
        $stmt = $conn->prepare("
            INSERT INTO learning_progress_dp 
            (user_id, course_id, progress_state, optimal_path_score, completion_probability, estimated_completion_time, last_calculated)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            progress_state = ?, 
            optimal_path_score = ?, 
            completion_probability = ?, 
            estimated_completion_time = ?, 
            last_calculated = NOW()
        ");
        
        if ($stmt === false) {
            // If prepare fails, just return true (don't crash the system)
            return true;
        }
        
        $stmt->bind_param("isidddisid", 
            $userId, $courseId, $progressJson, $optimalPathScore, $completionProbability, $estimatedCompletionTime,
            $progressJson, $optimalPathScore, $completionProbability, $estimatedCompletionTime
        );
        
        return $stmt->execute();
    }
    
    /**
     * Create learning_progress_dp table if it doesn't exist
     */
    private function createProgressTrackingTable() {
        $conn = $this->db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS `learning_progress_dp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `course_id` int(11) NOT NULL,
            `lesson_id` int(11) DEFAULT NULL,
            `progress_state` json NOT NULL,
            `optimal_path_score` decimal(5,2) DEFAULT 0.00,
            `completion_probability` decimal(5,4) DEFAULT 0.0000,
            `estimated_completion_time` int(11) DEFAULT 0,
            `last_calculated` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_course` (`user_id`, `course_id`),
            KEY `idx_user_progress` (`user_id`),
            KEY `idx_course_progress` (`course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->query($sql);
    }
    
    /**
     * Get comprehensive progress report
     */
    public function getProgressReport($userId, $courseId) {
        $progress = $this->calculateLearningProgress($userId, $courseId);
        
        // Add additional analytics
        $progress['learning_trends'] = $this->calculateLearningTrends($userId, $courseId);
        $progress['skill_gaps'] = $this->identifySkillGaps($userId, $courseId);
        $progress['recommendations'] = $this->generateProgressRecommendations($progress);
        
        return $progress;
    }
    
    /**
     * Calculate learning trends over time
     */
    private function calculateLearningTrends($userId, $courseId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as activities,
                SUM(interaction_value) as total_value
            FROM user_interactions
            WHERE user_id = ? AND course_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate trend
        if (count($data) >= 7) {
            $recentWeek = array_slice($data, -7);
            $previousWeek = array_slice($data, -14, 7);
            
            $recentAvg = array_sum(array_column($recentWeek, 'total_value')) / 7;
            $previousAvg = array_sum(array_column($previousWeek, 'total_value')) / 7;
            
            $trend = $recentAvg > $previousAvg ? 'improving' : 'declining';
            $trendPercentage = round((($recentAvg - $previousAvg) / $previousAvg) * 100, 2);
        } else {
            $trend = 'insufficient_data';
            $trendPercentage = 0;
        }
        
        return [
            'data' => $data,
            'trend' => $trend,
            'trend_percentage' => $trendPercentage
        ];
    }
    
    /**
     * Identify skill gaps
     */
    private function identifySkillGaps($userId, $courseId) {
        $progress = $this->calculateLearningProgress($userId, $courseId);
        $currentState = $progress['progress_state']['current_state'];
        
        $skillGaps = [];
        
        foreach ($currentState as $lessonId => $state) {
            if ($state === 'completed_weak' || $state === 'not_started') {
                $skillGaps[] = [
                    'lesson_id' => $lessonId,
                    'current_state' => $state,
                    'priority' => $state === 'not_started' ? 'high' : 'medium',
                    'action' => $state === 'not_started' ? 'Start learning' : 'Review and improve'
                ];
            }
        }
        
        return $skillGaps;
    }
    
    /**
     * Generate progress-based recommendations
     */
    private function generateProgressRecommendations($progress) {
        $recommendations = [];
        
        // Based on completion probability
        if ($progress['completion_probability'] < 0.5) {
            $recommendations[] = [
                'type' => 'study_plan',
                'title' => 'Intensive Study Plan',
                'description' => 'Create a structured study schedule with daily goals',
                'priority' => 'high'
            ];
        }
        
        // Based on learning velocity
        if ($progress['progress_state']['learning_velocity'] < 2) {
            $recommendations[] = [
                'type' => 'time_management',
                'title' => 'Time Management',
                'description' => 'Allocate specific time slots for learning',
                'priority' => 'medium'
            ];
        }
        
        // Based on engagement
        if ($progress['progress_state']['engagement_score'] < 50) {
            $recommendations[] = [
                'type' => 'interactive_content',
                'title' => 'Interactive Learning',
                'description' => 'Try quizzes, discussions, and hands-on exercises',
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
}
?>
