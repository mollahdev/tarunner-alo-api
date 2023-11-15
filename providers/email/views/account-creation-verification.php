<?php 
    $account = get_user_by( 'id', $user_id );
    $full_name = $account->first_name . ' ' . $account->last_name;
    $verification_key = $account->user_activation_key;
?>

<table>
    <tr>
        <td>
            <h1>Hi, <?php esc_html_e( $full_name ) ?></h1>
            <p>Thank you for registering with us. Please click on the link below to verify your account.</p>
            <h2><?php esc_html_e( $verification_key ) ?></h2>
        </td>
    </tr>
</table>