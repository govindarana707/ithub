-- =====================================================
-- Database Performance Monitoring & Optimization
-- =====================================================
-- Purpose: Monitor database performance and provide optimization recommendations
-- Version: 1.0.0
-- Date: 2026-03-20

-- =====================================================
-- Performance Analysis Queries
-- =====================================================

-- 1. Slow Query Analysis
SELECT 
    DIGEST_TEXT as query,
    COUNT_STAR as execution_count,
    AVG_TIMER_WAIT/1000000000 as avg_time_seconds,
    MAX_TIMER_WAIT/1000000000 as max_time_seconds,
    SUM_ROWS_EXAMINED/COUNT_STAR as avg_rows_examined,
    SUM_ROWS_SENT/COUNT_STAR as avg_rows_returned,
    FIRST_SEEN as first_seen,
    LAST_SEEN as last_seen
FROM performance_schema.events_statements_summary_by_digest 
WHERE DIGEST_TEXT NOT LIKE '%performance_schema%'
ORDER BY AVG_TIMER_WAIT DESC 
LIMIT 20;

-- 2. Table Size Analysis
SELECT 
    table_schema,
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS table_size_mb,
    ROUND((data_length / 1024 / 1024), 2) AS data_size_mb,
    ROUND((index_length / 1024 / 1024), 2) AS index_size_mb,
    table_rows,
    ROUND((index_length / data_length) * 100, 2) AS index_percentage
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;

-- 3. Index Usage Analysis
SELECT 
    table_schema,
    table_name,
    index_name,
    seq_in_index,
    column_name,
    cardinality,
    sub_part,
    packed,
    nullable,
    index_type
FROM information_schema.STATISTICS 
WHERE table_schema = DATABASE()
ORDER BY table_name, index_name, seq_in_index;

-- 4. Missing Indexes Analysis
SELECT 
    s.table_schema,
    s.table_name,
    s.column_name,
    'Potential missing index' as recommendation
FROM information_schema.STATISTICS s
JOIN information_schema.COLUMNS c ON s.table_schema = c.table_schema 
    AND s.table_name = c.table_name 
    AND s.column_name = c.column_name
WHERE s.table_schema = DATABASE()
    AND c.table_name IN (
        SELECT DISTINCT table_name 
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE()
    )
    AND s.table_name NOT LIKE 'v_%'
    AND s.index_name = 'PRIMARY'
GROUP BY s.table_schema, s.table_name, s.column_name
HAVING COUNT(*) = 1;

-- 5. Fragmentation Analysis
SELECT 
    table_schema,
    table_name,
    ROUND(data_free/1024/1024, 2) AS fragmentation_mb,
    ROUND(data_free/(data_length+index_length)*100, 2) AS fragmentation_percentage,
    engine
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
    AND data_free > 0
ORDER BY fragmentation_mb DESC;

-- =====================================================
-- Optimization Recommendations
-- =====================================================

-- 6. Tables that need optimization
SELECT 
    'Tables needing optimization' as analysis_type,
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows,
    CASE 
        WHEN table_rows > 100000 THEN 'Consider partitioning'
        WHEN (data_length + index_length) > 100 * 1024 * 1024 THEN 'Consider optimization'
        WHEN table_rows = 0 THEN 'Empty table - consider cleanup'
        ELSE 'Normal size'
    END as recommendation
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
    AND table_name NOT LIKE 'v_%'
ORDER BY (data_length + index_length) DESC;

-- 7. Unused Indexes
SELECT 
    s.table_schema,
    s.table_name,
    s.index_name,
    'Unused index' as issue_type,
    'Consider dropping if not needed' as recommendation
FROM information_schema.STATISTICS s
WHERE s.table_schema = DATABASE()
    AND s.index_name NOT IN ('PRIMARY')
    AND s.table_name NOT LIKE 'v_%'
    AND s.index_name NOT IN (
        SELECT DISTINCT index_name 
        FROM performance_schema.table_io_waits_summary_by_index_usage 
        WHERE object_schema = DATABASE()
    )
GROUP BY s.table_schema, s.table_name, s.index_name;

-- 8. Full Table Scan Analysis
SELECT 
    object_schema,
    object_name,
    index_name,
    count_star,
    sum_timer_wait/1000000000 as total_time_seconds,
    count_read,
    sum_timer_wait/count_star as avg_time_seconds
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = DATABASE()
    AND index_name IS NULL
    AND count_star > 100
ORDER BY sum_timer_wait DESC;

-- =====================================================
-- Automated Optimization Scripts
-- =====================================================

-- 9. Optimize All Tables (Safe for InnoDB)
-- Uncomment and run during maintenance window
/*
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name SEPARATOR ',') INTO @tables
FROM information_schema.TABLES 
WHERE table_schema = DATABASE() 
    AND engine = 'InnoDB'
    AND table_name NOT LIKE 'v_%';

SET @sql = CONCAT('OPTIMIZE TABLE ', @tables);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
*/

-- 10. Analyze All Tables
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name SEPARATOR ',') INTO @tables
FROM information_schema.TABLES 
WHERE table_schema = DATABASE() 
    AND table_name NOT LIKE 'v_%';

SET @sql = CONCAT('ANALYZE TABLE ', @tables);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Performance Monitoring Views
-- =====================================================

-- Create monitoring view
CREATE OR REPLACE VIEW v_database_performance AS
SELECT 
    'Database Performance Overview' as metric_type,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = DATABASE()) as total_tables,
    (SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = DATABASE()) as total_size_bytes,
    (SELECT SUM(table_rows) FROM information_schema.TABLES WHERE table_schema = DATABASE()) as total_rows,
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE table_schema = DATABASE()) as total_indexes,
    (SELECT COUNT(*) FROM information_schema.VIEWS WHERE table_schema = DATABASE()) as total_views,
    (SELECT COUNT(*) FROM information_schema.ROUTINES WHERE routine_schema = DATABASE()) as total_procedures,
    NOW() as last_updated;

-- Create slow queries view
CREATE OR REPLACE VIEW v_slow_queries AS
SELECT 
    DIGEST_TEXT as query,
    COUNT_STAR as execution_count,
    AVG_TIMER_WAIT/1000000000 as avg_time_seconds,
    MAX_TIMER_WAIT/1000000000 as max_time_seconds,
    SUM_ROWS_EXAMINED/COUNT_STAR as avg_rows_examined,
    FIRST_SEEN as first_seen,
    LAST_SEEN as last_seen,
    CASE 
        WHEN AVG_TIMER_WAIT/1000000000 > 1 THEN 'Critical'
        WHEN AVG_TIMER_WAIT/1000000000 > 0.5 THEN 'Warning'
        ELSE 'Normal'
    END as performance_level
FROM performance_schema.events_statements_summary_by_digest 
WHERE DIGEST_TEXT NOT LIKE '%performance_schema%'
    AND COUNT_STAR > 10
ORDER BY AVG_TIMER_WAIT DESC;

-- Create table health view
CREATE OR REPLACE VIEW v_table_health AS
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows,
    ROUND((index_length / data_length) * 100, 2) AS index_percentage,
    ROUND(data_free/1024/1024, 2) AS fragmentation_mb,
    CASE 
        WHEN table_rows = 0 THEN 'Empty'
        WHEN data_free > (data_length + index_length) * 0.1 THEN 'High Fragmentation'
        WHEN (index_length / data_length) > 0.5 THEN 'Over-indexed'
        WHEN table_rows > 100000 THEN 'Large Table'
        ELSE 'Healthy'
    END as health_status,
    CASE 
        WHEN table_rows = 0 THEN 'Consider cleanup'
        WHEN data_free > (data_length + index_length) * 0.1 THEN 'Run OPTIMIZE TABLE'
        WHEN (index_length / data_length) > 0.5 THEN 'Review indexes'
        WHEN table_rows > 100000 THEN 'Consider partitioning'
        ELSE 'No action needed'
    END as recommendation
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
    AND table_name NOT LIKE 'v_%'
ORDER BY (data_length + index_length) DESC;

-- =====================================================
-- Automated Health Check
-- =====================================================

-- Create stored procedure for health check
DELIMITER //
CREATE PROCEDURE sp_database_health_check()
BEGIN
    DECLARE total_tables INT DEFAULT 0;
    DECLARE total_size_mb DECIMAL(10,2) DEFAULT 0;
    DECLARE total_rows BIGINT DEFAULT 0;
    DECLARE fragmented_tables INT DEFAULT 0;
    DECLARE large_tables INT DEFAULT 0;
    DECLARE empty_tables INT DEFAULT 0;
    
    -- Get overall statistics
    SELECT COUNT(*) INTO total_tables
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND table_name NOT LIKE 'v_%';
    
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) INTO total_size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND table_name NOT LIKE 'v_%';
    
    SELECT SUM(table_rows) INTO total_rows
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND table_name NOT LIKE 'v_%';
    
    -- Count problematic tables
    SELECT COUNT(*) INTO fragmented_tables
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND data_free > (data_length + index_length) * 0.1
    AND table_name NOT LIKE 'v_%';
    
    SELECT COUNT(*) INTO large_tables
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND table_rows > 100000
    AND table_name NOT LIKE 'v_%';
    
    SELECT COUNT(*) INTO empty_tables
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND table_rows = 0
    AND table_name NOT LIKE 'v_%';
    
    -- Return health report
    SELECT 
        'Database Health Report' as report_type,
        total_tables,
        total_size_mb,
        total_rows,
        fragmented_tables,
        large_tables,
        empty_tables,
        CASE 
            WHEN fragmented_tables > 0 OR large_tables > 5 THEN 'Needs Attention'
            WHEN empty_tables > total_tables * 0.3 THEN 'Needs Cleanup'
            ELSE 'Healthy'
        END as overall_status,
        NOW() as check_time;
        
    -- Show problematic tables
    SELECT 
        table_name,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
        table_rows,
        CASE 
            WHEN table_rows = 0 THEN 'Empty'
            WHEN data_free > (data_length + index_length) * 0.1 THEN 'High Fragmentation'
            WHEN table_rows > 100000 THEN 'Large Table'
            ELSE 'Healthy'
        END as issue_type
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND (
        table_rows = 0 
        OR data_free > (data_length + index_length) * 0.1 
        OR table_rows > 100000
    )
    AND table_name NOT LIKE 'v_%'
    ORDER BY (data_length + index_length) DESC;
END //
DELIMITER ;

-- =====================================================
-- Performance Optimization Recommendations
-- =====================================================

-- Create optimization recommendations procedure
DELIMITER //
CREATE PROCEDURE sp_optimization_recommendations()
BEGIN
    -- General recommendations
    SELECT 
        'General Recommendations' as category,
        recommendation,
        priority,
        estimated_impact
    FROM (
        SELECT 
            'Add indexes to frequently queried columns' as recommendation,
            'High' as priority,
            'Significant' as estimated_impact
        UNION ALL
        SELECT 
            'Optimize large tables (>100MB)' as recommendation,
            'Medium' as priority,
            'Moderate' as estimated_impact
        UNION ALL
        SELECT 
            'Remove unused indexes' as recommendation,
            'Low' as priority,
            'Minor' as estimated_impact
        UNION ALL
        SELECT 
            'Implement query result caching' as recommendation,
            'High' as priority,
            'Significant' as estimated_impact
    ) as recommendations;
    
    -- Specific table recommendations
    SELECT 
        'Table-Specific Recommendations' as category,
        CONCAT('Table: ', table_name) as recommendation,
        CASE 
            WHEN table_rows = 0 THEN 'Low'
            WHEN data_free > (data_length + index_length) * 0.1 THEN 'High'
            WHEN table_rows > 100000 THEN 'Medium'
            ELSE 'Low'
        END as priority,
        CASE 
            WHEN table_rows = 0 THEN 'Minor'
            WHEN data_free > (data_length + index_length) * 0.1 THEN 'Significant'
            WHEN table_rows > 100000 THEN 'Moderate'
            ELSE 'Minor'
        END as estimated_impact
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE() 
    AND (
        table_rows = 0 
        OR data_free > (data_length + index_length) * 0.1 
        OR table_rows > 100000
    )
    AND table_name NOT LIKE 'v_%'
    ORDER BY 
        CASE 
            WHEN table_rows = 0 THEN 1
            WHEN data_free > (data_length + index_length) * 0.1 THEN 2
            WHEN table_rows > 100000 THEN 3
            ELSE 4
        END;
END //
DELIMITER ;

-- =====================================================
-- Usage Instructions
-- =====================================================

/*
-- To monitor database performance:

-- 1. Run health check
CALL sp_database_health_check();

-- 2. Get optimization recommendations
CALL sp_optimization_recommendations();

-- 3. Check slow queries
SELECT * FROM v_slow_queries LIMIT 10;

-- 4. Check table health
SELECT * FROM v_table_health;

-- 5. Monitor database performance
SELECT * FROM v_database_performance;

-- 6. Analyze specific table performance
SELECT * FROM v_table_health WHERE table_name = 'users';

-- 7. Check index usage
SELECT * FROM performance_schema.table_io_waits_summary_by_index_usage 
WHERE object_schema = DATABASE() 
ORDER BY sum_timer_wait DESC;

-- 8. Monitor query performance in real-time
SELECT 
    thread_id,
    sql_text,
    time,
    state
FROM performance_schema.threads 
WHERE command = 'Query' 
    AND sql_text IS NOT NULL;

-- To run optimization (during maintenance window):

-- 1. Analyze all tables (safe)
CALL sp_analyze_all_tables();

-- 2. Optimize fragmented tables (use with caution)
-- Run only on tables with high fragmentation
SELECT CONCAT('OPTIMIZE TABLE ', table_name, ';') as optimization_command
FROM information_schema.TABLES 
WHERE table_schema = DATABASE() 
    AND data_free > (data_length + index_length) * 0.1
    AND table_name NOT LIKE 'v_%';
*/

-- =====================================================
-- Monitoring Schedule Recommendations
-- =====================================================

/*
Daily:
- Run sp_database_health_check()
- Check v_slow_queries for new entries
- Monitor table sizes

Weekly:
- Run sp_optimization_recommendations()
- Review index usage
- Check for new large tables

Monthly:
- Run full table optimization (during maintenance window)
- Review and update indexes
- Analyze query patterns

Quarterly:
- Review database schema for optimization opportunities
- Consider partitioning for very large tables
- Evaluate need for additional indexes
*/
