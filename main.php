<?php
/*
Plugin Name:  Newsletter Subscribe Widget for MailChimp
Plugin URI:   https://codingstories.net/building-wordpress-widgets-vue-js/
Description:  Newsletter subscription in mailchimp lists, which subscribes the users without refreshing the webpage using AJAX
Version:      1.0.1
Author:       Pavel Petrov
Author URI:   http://webbamboo.net/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

/***** Register Widget *****/
require_once("mailchimp-widget.php");
add_action('widgets_init', function() {
    register_widget('MailchimpWidget');
});
//1. Enqueue Vue.js
add_action( 'wp_enqueue_scripts', function(){
    //VueJS
    wp_enqueue_script( 'VueJS', plugin_dir_url( __FILE__ ).'js/vue.js', false );

    //SweetAlert2
    wp_enqueue_script( 'Swal2', plugin_dir_url( __FILE__ ).'js/sweetalert2.common.min.js', false );
    wp_enqueue_style( 'Swal2-CSS', plugin_dir_url( __FILE__ ).'css/sweetalert2.min.css' );

    //Our code
    wp_enqueue_script( 'NewsletterWidget', plugin_dir_url( __FILE__ ).'js/newsletter-widget.js', ['VueJS', 'Swal2', 'jquery'], false, true );
    //2.Create the nonce and the ajaxurl objects
    $params = array(
      'ajaxurl' => admin_url('admin-ajax.php', $protocol),
      'ajax_nonce' => wp_create_nonce('mailchimp_newsletter_widget'),
    );
    wp_localize_script( 'NewsletterWidget', 'ajax_object', $params );
} );
//3.Register the AJAX action
add_action( 'wp_ajax_newsletter_widget', 'newsletter_widget_ajax' );
add_action( 'wp_ajax_nopriv_newsletter_widget', 'newsletter_widget_ajax' );
function newsletter_widget_ajax(){
    check_ajax_referer( 'mailchimp_newsletter_widget', 'security' );

    $name = filter_input(INPUT_POST, 'name');
    $email = filter_input(INPUT_POST, 'email');

    $validate = function() use($name, $email) {
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $errors[] = 'Email address is invalid';
        }

        if(empty($name))
        {
            $errors[] = 'Name is required';
        }
        return implode(", ", $errors);
    };
    if(!empty($validate()))
    {
        echo json_encode(['success' => false, 'http' => false, 'error' => $validate()]);
    }
    else
    {
        //Try to subscribe
        $apiResponse = syncMailchimp(new class($name, $email) implements MailchimpData{
            private $name;
            private $names;
            private $email;
            private $type;
            public function __construct($name, $email)
            {
                $this->name = $name;
                $this->names = explode(" ", $name);
                $this->email = $email;
                // "subscribed","unsubscribed","cleaned","pending"
                $this->type = 'subscribed';
            }
            public function getFirstname()
            {
                return count($this->names) > 0 ? $this->names[0] : $this->name;
            }
            public function getLastname()
            {
                if(count($this->names) > 0)
                {
                    unset($this->names[0]);
                    return implode(" ", $this->names);
                }
            }
            public function getEmail()
            {
                return $this->email;
            }
            public function getType()
            {
                return $this->type;
            }
        });
        if($apiResponse == 200)
        {
            echo json_encode(['success' => true, 'http' => 200, 'error' => false]);
        }
        else
        {
            echo json_encode(['success' => false, 'http' => $apiResponse, 'error' => false]);
        }
    }
    die();
}
interface MailchimpData {
    public function getFirstname();
    public function getLastname();
    public function getEmail();
    public function getType();
}

function syncMailchimp(MailchimpData $data) {
    $widgetInstance = new MailchimpWidget();
    $widgetSettings = reset($widgetInstance->get_settings());

    $apiKey = $widgetSettings['apiKey'];
    $listId = $widgetSettings['listId'];

    $memberId = md5(strtolower($data->getEmail()));
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1); //eg. us13
    //http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

    $json = json_encode([
        'email_address' => $data->getEmail(),
        'status'        => $data->getType(),
        'merge_fields'  => [
            'FNAME'     => $data->getFirstname(),
            'LNAME'     => $data->getLastname()
        ]
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}
