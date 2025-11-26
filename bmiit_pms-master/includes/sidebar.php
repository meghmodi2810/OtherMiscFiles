<?php
/**
 * Sidebar Navigation - BMIIT PMS
 * Role-aware navigation menu with icons
 * Shows different menu items based on user role
 */

// Get current user role
$user_role = $_SESSION['user']['role'] ?? 'student';

// Helper function to check if link is active
function is_active($path) {
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($current_path, $path) !== false ? 'active' : '';
}
?>

<!-- Sidebar -->
<aside class="sidebar">
    
    <!-- Common Navigation for All Roles -->
    <div class="nav-section">
        <div class="nav-section-title">Main</div>
        <div class="nav-item">
            <a href="/bmiit_pms/<?php echo $user_role; ?>/<?php echo $user_role; ?>_home.php" class="nav-link <?php echo is_active('home'); ?>">
                <i data-feather="home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="/bmiit_pms/common/notifications.php" class="nav-link <?php echo is_active('notifications'); ?>">
                <i data-feather="bell"></i>
                <span>Notifications</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="/bmiit_pms/common/messages.php" class="nav-link <?php echo is_active('messages'); ?>">
                <i data-feather="message-square"></i>
                <span>Messages</span>
            </a>
        </div>
    </div>

    <?php if ($user_role === 'admin'): ?>
        <!-- Admin Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">User Management</div>
            
            <div class="nav-dropdown">
                <button class="dropdown-trigger">
                    <div class="dropdown-trigger-content">
                        <i data-feather="users"></i>
                        <span>Students</span>
                    </div>
                    <i data-feather="chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="/bmiit_pms/admin/add_student_manual.php" class="nav-link">
                        <i data-feather="user-plus"></i>
                        <span>Add Student</span>
                    </a>
                    <a href="/bmiit_pms/admin/add_student_bulk.php" class="nav-link">
                        <i data-feather="upload"></i>
                        <span>Bulk Upload</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-dropdown">
                <button class="dropdown-trigger">
                    <div class="dropdown-trigger-content">
                        <i data-feather="briefcase"></i>
                        <span>Faculty</span>
                    </div>
                    <i data-feather="chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="/bmiit_pms/admin/add_faculty_manual.php" class="nav-link">
                        <i data-feather="user-plus"></i>
                        <span>Add Faculty</span>
                    </a>
                    <a href="/bmiit_pms/admin/add_faculty_bulk.php" class="nav-link">
                        <i data-feather="upload"></i>
                        <span>Bulk Upload</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="/bmiit_pms/admin/manage_users.php" class="nav-link <?php echo is_active('manage_users'); ?>">
                    <i data-feather="settings"></i>
                    <span>Manage All Users</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Academic</div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/create_new_sem.php" class="nav-link <?php echo is_active('create_new_sem'); ?>">
                    <i data-feather="calendar"></i>
                    <span>Create Semester</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/allocate_guides.php" class="nav-link <?php echo is_active('allocate_guides'); ?>">
                    <i data-feather="user-check"></i>
                    <span>Allocate Guides</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/projects_dashboard.php" class="nav-link <?php echo is_active('projects_dashboard'); ?>">
                    <i data-feather="folder"></i>
                    <span>Projects Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/manage_milestones.php" class="nav-link <?php echo is_active('manage_milestones'); ?>">
                    <i data-feather="flag"></i>
                    <span>Manage Milestones</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/manage_groups.php" class="nav-link <?php echo is_active('manage_groups'); ?>">
                    <i data-feather="layers"></i>
                    <span>Manage Groups</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/analytics_dashboard.php" class="nav-link <?php echo is_active('analytics'); ?>">
                    <i data-feather="bar-chart-2"></i>
                    <span>Analytics</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/backup_export.php" class="nav-link <?php echo is_active('backup'); ?>">
                    <i data-feather="download"></i>
                    <span>Backup & Export</span>
                </a>
            </div>
        </div>

    <?php elseif ($user_role === 'faculty'): ?>
        <!-- Faculty Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Projects</div>
            <div class="nav-item">
                <a href="/bmiit_pms/faculty/my_projects.php" class="nav-link <?php echo is_active('my_projects'); ?>">
                    <i data-feather="folder"></i>
                    <span>My Projects</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/faculty/review_project_ideas.php" class="nav-link <?php echo is_active('review_project'); ?>">
                    <i data-feather="eye"></i>
                    <span>Review Ideas</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/faculty/grade_milestones.php" class="nav-link <?php echo is_active('grade_milestones'); ?>">
                    <i data-feather="check-circle"></i>
                    <span>Grade Milestones</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Students</div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i data-feather="users"></i>
                    <span>My Students</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i data-feather="layers"></i>
                    <span>View Groups</span>
                </a>
            </div>
        </div>

    <?php else: // Student ?>
        <?php 
        // Get student's group info for dynamic menu
        $sidebar_student_id = $_SESSION['student_id'] ?? null;
        $sidebar_group_info = null;
        if ($sidebar_student_id) {
            $sidebar_stmt = $conn->prepare("
                SELECT gm.group_id, gm.role, g.finalized
                FROM group_members gm
                JOIN groups g ON gm.group_id = g.group_id
                WHERE gm.student_id = ?
            ");
            $sidebar_stmt->bind_param("i", $sidebar_student_id);
            $sidebar_stmt->execute();
            $sidebar_group_info = $sidebar_stmt->get_result()->fetch_assoc();
            $sidebar_stmt->close();
        }
        ?>
        <!-- Student Navigation - Dynamic -->
        <div class="nav-section">
            <div class="nav-section-title">My Group</div>
            
            <div class="nav-dropdown">
                <button class="dropdown-trigger">
                    <div class="dropdown-trigger-content">
                        <i data-feather="users"></i>
                        <span>Group Actions</span>
                    </div>
                    <i data-feather="chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <?php if (!$sidebar_group_info): ?>
                        <!-- No group - show creation and browsing options -->
                        <a href="/bmiit_pms/student/create_group.php" class="nav-link">
                            <i data-feather="plus-circle"></i>
                            <span>Create Group</span>
                        </a>
                        <a href="/bmiit_pms/student/browse_groups.php" class="nav-link">
                            <i data-feather="search"></i>
                            <span>Browse Groups</span>
                        </a>
                        <a href="/bmiit_pms/student/accept_invite.php" class="nav-link">
                            <i data-feather="mail"></i>
                            <span>View Invites</span>
                        </a>
                    <?php elseif ($sidebar_group_info && !$sidebar_group_info['finalized']): ?>
                        <!-- Forming group - show manage and role-specific options -->
                        <?php if ($sidebar_group_info['role'] === 'leader'): ?>
                            <!-- Leader can manage -->
                            <a href="/bmiit_pms/student/manage_group.php" class="nav-link">
                                <i data-feather="settings"></i>
                                <span>Manage Group</span>
                            </a>
                        <?php else: ?>
                            <!-- Members can view -->
                            <a href="/bmiit_pms/student/manage_group.php" class="nav-link">
                                <i data-feather="eye"></i>
                                <span>View Group</span>
                            </a>
                        <?php endif; ?>
                        <a href="/bmiit_pms/student/accept_invite.php" class="nav-link">
                            <i data-feather="mail"></i>
                            <span>View Invites</span>
                        </a>
                        <?php if ($sidebar_group_info['role'] === 'leader'): ?>
                            <!-- Leader-only options -->
                            <a href="/bmiit_pms/student/invite.php" class="nav-link">
                                <i data-feather="user-plus"></i>
                                <span>Invite Members</span>
                            </a>
                            <a href="/bmiit_pms/student/transfer_leader.php" class="nav-link">
                                <i data-feather="repeat"></i>
                                <span>Transfer Leadership</span>
                            </a>
                            <a href="/bmiit_pms/student/dissolve_group.php" class="nav-link">
                                <i data-feather="x-circle"></i>
                                <span>Dissolve Group</span>
                            </a>
                        <?php else: ?>
                            <!-- Member-only option -->
                            <a href="/bmiit_pms/student/leave_group.php" class="nav-link">
                                <i data-feather="log-out"></i>
                                <span>Leave Group</span>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($sidebar_group_info && $sidebar_group_info['finalized']): ?>
                        <!-- Finalized group - limited view -->
                        <a href="/bmiit_pms/student/manage_group.php" class="nav-link">
                            <i data-feather="eye"></i>
                            <span>View Group</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Projects</div>
            <div class="nav-item">
                <a href="/bmiit_pms/student/submit_project_idea.php" class="nav-link <?php echo is_active('submit_project'); ?>">
                    <i data-feather="file-plus"></i>
                    <span>Submit Project Idea</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/student/submit_milestone.php" class="nav-link <?php echo is_active('submit_milestone'); ?>">
                    <i data-feather="upload-cloud"></i>
                    <span>Submit Milestone</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="/bmiit_pms/student/progress_tracker.php" class="nav-link <?php echo is_active('progress'); ?>">
                    <i data-feather="trending-up"></i>
                    <span>Progress Tracker</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($user_role === 'admin'): ?>
        <!-- Admin-only Section -->
        <div class="nav-section">
            <div class="nav-section-title">Administration</div>
            <div class="nav-item">
                <a href="/bmiit_pms/admin/add_admin_manual.php" class="nav-link <?php echo is_active('add_admin'); ?>">
                    <i data-feather="shield"></i>
                    <span>Add Administrator</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Profile Section (Common) -->
    <div class="nav-section">
        <div class="nav-section-title">Account</div>
        <div class="nav-item">
            <a href="#" class="nav-link">
                <i data-feather="user"></i>
                <span>My Profile</span>
            </a>
        </div>
    </div>
    
</aside>
