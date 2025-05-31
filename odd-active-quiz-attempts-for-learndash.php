<?php
/**
 * Plugin Name: Active Quiz Attempts for LearnDash
 * Plugin URI: https://orangedotdevelopment.com/software/wordpress/plugins/active-quiz-attempts-for-learndash/
 * Description: Displays and allows for the deletion of saved in progress quiz attempts.
 * Version: 1.0.0
 * Author: Jarret
 * Author URI: https://orangedotdevelopment.com
 * Text Domain: odd-active-quiz-attempts-for-learndash
 * Domain Path: /languages
 * Requires Plugins: sfwd-lms
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/Users/jarretcade/odd/wp-content/plugins/odd-active-quiz-attemtps-for-learndash
add_action( 'edit_user_profile', 'odd_active_quiz_attempts_display' );
add_action( 'show_user_profile', 'odd_active_quiz_attempts_display' );
add_action( 'init', 'odd_active_quiz_attempts_textdomain' );

function odd_active_quiz_attempts_textdomain() {
    load_plugin_textdomain( 'odd-active-quiz-attempts-for-learndash', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function odd_active_quiz_attempts_results() {
    global $wpdb, $user_id;

    $table_name = LDLMS_DB::get_table_name( 'user_activity' );
    $incomplete_quizzes_sql = $wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE activity_type = %s AND activity_completed = %d AND user_id = %d",
        'quiz',
        0,
        $user_id
    );
    
    $incomplete_quizzes = $wpdb->get_results( $incomplete_quizzes_sql, ARRAY_A );

    return $incomplete_quizzes;
}

function odd_active_quiz_attempts_display() {

    $incomplete_quizzes = odd_active_quiz_attempts_results();

    echo '<div id="#active-quiz-attempts">';

    echo '<h2>' . esc_html__( 'Active ', 'odd-active-quiz-attempts-for-learndash' ) .
    esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) ) .
    esc_html__( ' Attempts', 'odd-active-quiz-attempts-for-learndash' ) . '</h2>';

    if ( ! $incomplete_quizzes ) {
        echo sprintf(
            esc_html__( 'No active %s attempts found.', 'odd-active-quiz-attempts-for-learndash' ),
            LearnDash_Custom_Label::get_label( 'quiz' )
        );
    }

    foreach ( $incomplete_quizzes as $quiz ) {
        $quiz['resume_data'] = learndash_get_user_activity_meta( $quiz['activity_id'], 'quiz_resume_data' );

        $quiz_id = (int) $quiz['post_id'];
        $user_id = (int) $quiz['user_id'];
        $activity_id = (int) $quiz['activity_id'];
        
        if ( empty( $quiz['resume_data'] ) ) {
            echo sprintf(
                esc_html__( 'No saved %s attempts found!', 'odd-active-quiz-attempts-for-learndash' ),
                LearnDash_Custom_Label::get_label( 'quiz' )
            );
        }

        echo '<div class="active-quiz-attempt" id="quiz-id-' . $quiz_id . '">';

        $quiz_link = '<a href="' . esc_url( get_the_permalink( $quiz_id ) ) . '">' . esc_html( get_the_title( $quiz_id ) ) . '</a> -';
        $started_label = esc_html__( ' Started', 'odd-active-quiz-attempts-for-learndash' );
        $quiz_date = date_i18n(
            get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
            $quiz['activity_started']
        );
        $activity_id_label = esc_html__( '- Activity ID: ', 'odd-active-quiz-attempts-for-learndash' ) . esc_html( $activity_id );
        $link_text = esc_html__( 'Delete Attempt', 'odd-active-quiz-attempts-for-learndash' );
        $delete_link = current_user_can( 'wpProQuiz_edit_quiz' )
        ? '<a href="#"
            class="delete-active-quiz-attempt"
            data-odd-user-id="' . $user_id . '"
            data-odd-activity-id="' . $activity_id . '"
            data-odd-attempt-nonce="' . wp_create_nonce( 'remove_attempt_' . $user_id . '_' . $activity_id ) . '"
            >' . $link_text . '</a>'
        : '';

        echo sprintf(
            /* translators: 1: Quiz permalink 2: Start label 3: Quiz started date 4: Activity ID 5: Delete attempt link */
            '%1$s %2$s %3$s %4$s %5$s',
            $quiz_link,
            $started_label,
            $quiz_date,
            $activity_id_label,
            $delete_link
        );

        echo '</div>';
    }

    echo '</div>';

}

function odd_delete_quiz_attempt_script() {
    wp_enqueue_script(
        'delete-quiz-attempt',
        plugins_url( 'assets/js/delete-quiz-attempt.js', __FILE__ ),
        array( 'jquery' ),
        '1.0',
        true
    );

}
add_action( 'admin_enqueue_scripts', 'odd_delete_quiz_attempt_script' );

function odd_delete_active_quiz_attempt() {

    $user_id = (int) $_POST['user_id'];
    $activity_id = (int) $_POST['activity_id'];
    $nonce = isset( $_POST['attempt_nonce'] ) ? sanitize_text_field( $_POST['attempt_nonce'] ) : '';

    if (
        ! isset( $user_id, $activity_id ) &&
        ! wp_verify_nonce( $nonce, 'remove_attempt_' . $user_id . '_' . $activity_id ) ||
        ! current_user_can( 'wpProQuiz_edit_quiz' ) )
    {
        wp_send_json_error( 'Invalid request' );
    }

    $deleted = learndash_delete_user_activity( $activity_id );

    if ( false === $deleted ) {
        wp_send_json_error(
            sprintf(
                esc_html__( 'Failed to delete active %s attempt for user ID %d and activity ID %d.', 'odd-active-quiz-attempts-for-learndash' ),
                LearnDash_Custom_Label::get_label( 'quiz' ),
                $user_id,
                $activity_id
            )
        );
    }

    wp_send_json_success( 
        sprintf(
            esc_html__( 'Successfully deleted active %s attempt for user ID %d and activity ID %d.', 'odd-active-quiz-attempts-for-learndash' ),
            LearnDash_Custom_Label::get_label( 'quiz' ),
            $user_id,
            $activity_id
        )
    );
}
add_action( 'wp_ajax_delete_active_quiz_attempt', 'odd_delete_active_quiz_attempt' );