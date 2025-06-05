/**
 * Styles for the Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Common styles */
.sportsgrades-search-container {
    margin-bottom: 20px;
}

.sportsgrades-results-container {
    margin-top: 20px;
}

.sportsgrades-grade-container {
    margin-top: 20px;
}

/* Course list */
.sportsgrades-course-list {
    max-height: 400px;
    overflow-y: auto;
}

.sportsgrades-course-item {
    transition: background-color 0.2s;
}

.sportsgrades-course-item:hover {
    background-color: #f8f9fa;
}

.sportsgrades-course-item.active {
    background-color: #e9ecef;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sportsgrades-course-list {
        max-height: 200px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}
