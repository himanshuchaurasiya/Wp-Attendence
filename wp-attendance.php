<?php
/**
 * Plugin Name: WP Attendance Plugin
 */

// step1:- Create database on plugin activation

register_activation_hook(__FILE__, 'wp_attendance_create_table');

function wp_attendance_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . "attendance";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        date date NOT NULL,
        present varchar(10) NOT NULL,
        punch_in time DEFAULT NULL,
        punch_out time DEFAULT NULL,
        total_hours float DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Step-3:- Add Admin Menu
add_action('admin_menu', 'wp_attendance_admin_menu');

function wp_attendance_admin_menu() {

    // MAIN MENU
    add_menu_page(
        'Attendance System',        // Page title
        'Attendance',               // Menu title
        'manage_options',           // Permission
        'attendance-dashboard',     // Slug
        'wp_attendance_dashboard',  // Callback function
        'dashicons-clock',          // Icon
        6                           // Position
    );



    // SUBMENU 1: Add New User
    add_submenu_page(
        'attendance-dashboard',     // Parent slug
        'Add New User',
        'Add User',
        'manage_options',
        'attendance-add-user',
        'wp_attendance_add_user'
    );

    // SUBMENU 2: Users List
    add_submenu_page(
        'attendance-dashboard',
        'Users List',
        'Users List',
        'manage_options',
        'attendance-users-list',
        'wp_attendance_users_list'
    );

    // SUBMENU 3: Add Attendance
    add_submenu_page(
        'attendance-dashboard',
        'Add Attendance',
        'Add Attendance',
        'manage_options',
        'attendance-add-attendance',
        'wp_attendance_add_attendance'
    );

    // SUBMENU 4: View Attendance
    add_submenu_page(
	    'attendance-dashboard',
	    'View Attendance',
	    'View Attendance',
	    'manage_options',
	    'attendance-view-attendance',
	    'wp_attendance_view_attendance'
	);

}

//Creating Attendence Page
function wp_attendance_dashboard() {
    ?>
    <div class="wrap">
        <h1>Attendance System Dashboard</h1>

        <style>
            .attendance-cards {
                display: flex;
                gap: 20px;
                margin-top: 25px;
            }

            .attendance-card {
                flex: 1;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                text-align: center;
                transition: 0.2s ease;
            }

            .attendance-card:hover {
                transform: translateY(-3px);
            }

            .attendance-card h2 {
                margin: 0 0 10px 0;
                font-size: 20px;
                color: #333;
            }

            .attendance-card p {
                margin: 0;
                font-size: 16px;
            }

            .attendance-card a {
                display: inline-block;
                margin-top: 10px;
                padding: 8px 15px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 4px;
            }

            .attendance-card a:hover {
                background: #005c89;
            }
        </style>

        <div class="attendance-cards">

            <div class="attendance-card">
                <h2>Add Attendance</h2>
                <p>Mark attendance for any user.</p>
                <a href="<?php echo admin_url('admin.php?page=attendance-add-attendance'); ?>">Go →</a>
            </div>

            <div class="attendance-card">
                <h2>All Attendance Records</h2>
                <p>View complete attendance history.</p>
                <a href="<?php echo admin_url('admin.php?page=attendance-view-attendance'); ?>">Go →</a>
            </div>

            <div class="attendance-card">
                <h2>User List</h2>
                <p>View all registered employees.</p>
                <a href="<?php echo admin_url('admin.php?page=attendance-user-list'); ?>">Go →</a>
            </div>

        </div>

    </div>
    <?php
}

// Step-4: Create employee role
register_activation_hook(__FILE__, 'wp_attendance_add_role');
function wp_attendance_add_role() {
    add_role('employee', 'Employee', [
        'read' => true
    ]);
}

//creating employee as new user role
function my_custom_roles_init() {
    if ( get_role('employee') === null ) {
        add_role(
            'employee',
            'Employee',
            array(
                'read' => true,
            )
        );
    }
}
add_action('init', 'my_custom_roles_init');


//STEP 4.2 — Create Add User Form (HTML)
function wp_attendance_add_user() {
    ?>
    <div class="wrap">
        <h1>Add New Employee</h1>
        <?php
        if ($msg = get_transient('custom_user_message')) {
            echo "<div style='padding:10px; background:#dff0d8; color:#3c763d; border-radius:4px; margin-bottom:15px;'>$msg</div>";
            delete_transient('custom_user_message');
        }
        ?>
        <form id="custom-registration-form" method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <label for="reg_username">Username:</label>
              <input type="text" id="reg_username" name="reg_username" required>

              <label for="reg_email">Email:</label>
              <input type="email" id="reg_email" name="reg_email" required>

              <label for="reg_password">Password:</label>
              <input type="password" id="reg_password" name="reg_password" required>

              <label for="reg_first_name">First Name:</label>
              <input type="text" id="reg_first_name" name="reg_first_name">

              <input type="hidden" name="action" value="custom_register_user">
              <?php wp_nonce_field('custom_user_registration_nonce', 'custom_user_registration_nonce_field'); ?>

              <button type="submit">Register</button>
        </form>
    </div>
    <?php
}

//STEP 4.3 — Process Form & Create User
function custom_handle_user_registration() {
    if (isset($_POST['action']) && $_POST['action'] === 'custom_register_user') {
        // Verify nonce
        if (!isset($_POST['custom_user_registration_nonce_field']) ||
            !wp_verify_nonce($_POST['custom_user_registration_nonce_field'], 'custom_user_registration_nonce')) {
            wp_die('Security check failed.');
        }

        $username    = sanitize_user($_POST['reg_username']);
        $email       = sanitize_email($_POST['reg_email']);
        $password    = $_POST['reg_password'];
        $first_name  = sanitize_text_field($_POST['reg_first_name']);

        $userdata = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => $password,
            'first_name'    => $first_name,
            'role'          => 'employee',
        );

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            $message = $user_id->get_error_message();
        } else {
            $message = "🎉 User registered successfully!";
        }

        // Store message in transient so it displays on same page
        set_transient('custom_user_message', $message, 60);

        wp_safe_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }
}
add_action('admin_post_custom_register_user', 'custom_handle_user_registration');
add_action('admin_post_nopriv_custom_register_user', 'custom_handle_user_registration');



//STEP 5.1 — Fetch All Users With Employee Role
function wp_attendance_users_list() {

    // Get all employee users
    $args = [
        'role'    => 'employee',
        'orderby' => 'display_name',
        'order'   => 'ASC'
    ];

    $users = get_users($args);

    ?>
    <div class="wrap">
        <h1>Employees List</h1>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>View Attendance</th>
                </tr>
            </thead>

            <tbody>
            <?php
            if (!empty($users)) {
                foreach ($users as $user) {
                    echo "<tr>
                            <td>{$user->ID}</td>
                            <td>{$user->display_name}</td>
                            <td>{$user->user_email}</td>
                            <td>
                                <a href='admin.php?page=view-attendance&user_id={$user->ID}' class='button button-primary'>
                                    View Attendance
                                </a>
                            </td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No employees found.</td></tr>";
            }
            ?>
            </tbody>

        </table>
    </div>
    <?php
}

//STEP 6:- Add Attendence Functionality
function wp_attendance_add_attendance() {

    global $wpdb;
    $table = $wpdb->prefix . "attendance";

    // Fetch employees for dropdown
    $employees = get_users(['role' => 'employee']);

    /* ----------------------------------------------
        FORM SUBMISSION
    ---------------------------------------------- */
    if ( isset($_POST['attendance_submit']) ) {

        if (! wp_verify_nonce($_POST['attendance_nonce'], 'save_attendance')) {
            die("Security check failed!");
        }

        $user_id   = intval($_POST['user_id']);
        $date      = sanitize_text_field($_POST['date']);
        $present   = sanitize_text_field($_POST['present']);
        $punch_in  = sanitize_text_field($_POST['punch_in']);
        $punch_out = sanitize_text_field($_POST['punch_out']);

        // Calculate hours
        $total_hours = 0;
        if ($present == 'yes' && $punch_in && $punch_out) {
            $total_hours = (strtotime($punch_out) - strtotime($punch_in)) / 3600;
            $total_hours = round($total_hours, 2);
        }

        // Insert database record
        $wpdb->insert(
            $table,
            [
                'user_id'     => $user_id,
                'date'        => $date,
                'present'     => $present,
                'punch_in'    => $punch_in,
                'punch_out'   => $punch_out,
                'total_hours' => $total_hours
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f']
        );

        echo "<div class='updated'><p><strong>Attendance saved successfully!</strong></p></div>";
    }

    ?>
    
    <div class="wrap">
        <h1>Add Attendance</h1>

        <form method="post">

            <?php wp_nonce_field('save_attendance', 'attendance_nonce'); ?>

            <table class="form-table">

                <!-- Employee -->
                <tr>
                    <th>Select Employee</th>
                    <td>
                        <select name="user_id" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $emp) : ?>
                                <option value="<?php echo $emp->ID; ?>">
                                    <?php echo $emp->display_name . " (" . $emp->user_email . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <!-- Attendance Date -->
                <tr>
                    <th>Date</th>
                    <td><input type="date" name="date" required></td>
                </tr>

                <!-- Present / Absent -->
                <tr>
                    <th>Present?</th>
                    <td>
                        <select name="present" required>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </td>
                </tr>

                <!-- Punch In -->
                <tr>
                    <th>Punch In Time</th>
                    <td><input type="time" name="punch_in"></td>
                </tr>

                <!-- Punch Out -->
                <tr>
                    <th>Punch Out Time</th>
                    <td><input type="time" name="punch_out"></td>
                </tr>

            </table>

            <input type="submit" name="attendance_submit" class="button button-primary" value="Save Attendance">

        </form>

    </div>

    <?php
}

//STEP 7:- View Attendence
function wp_attendance_view_attendance() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'attendance';

    // Fetch all attendance records
    $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");

    echo "<div class='wrap'>";
    echo "<h1>View Attendance</h1>";

    if (empty($records)) {
        echo "<p>No attendance records found.</p>";
        echo "</div>";
        return;
    }

    echo "<table class='widefat striped' style='margin-top:20px;'>";
    echo "<thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Date</th>
                <th>Status</th>
                <th>Punch In</th>
                <th>Punch Out</th>
                <th>Total Hours</th>
            </tr>
          </thead>";

    echo "<tbody>";

    foreach ($records as $record) {

        // Calculate total hours if not stored
        $total_hours = "";
        if (!empty($record->punch_in) && !empty($record->punch_out)) {
            $start = strtotime($record->punch_in);
            $end = strtotime($record->punch_out);
            $total_hours = round(($end - $start) / 3600, 2) . " hrs";
        }

        // Get user name
        $user_info = get_userdata($record->user_id);
        $user_name = $user_info ? $user_info->display_name : "Unknown";

        echo "<tr>
                <td>{$record->id}</td>
                <td>{$user_name}</td>
                <td>{$record->date}</td>
                <td>{$record->present}</td>
                <td>{$record->punch_in}</td>
                <td>{$record->punch_out}</td>
                <td>{$total_hours}</td>
             </tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}



