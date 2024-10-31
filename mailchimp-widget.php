<?php
class MailchimpWidget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'mailchimp_widget',
			'description' => 'Mailchimp Widget woth Vue.JS',
		);
		parent::__construct( 'mailchimp_widget', 'Mailchimp Widget', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		wp_enqueue_style( 'mailchimp-newsletter', plugin_dir_url( __FILE__ ).'css/newsletter.css' );
		// outputs the content of the widget
		?>
		<span id="newsletterWidget">
		    <span v-if="hidewidget">
		        <form class="news-letter" action="#">
	                <span v-if="subscribed">
	                    <img src="<?= plugin_dir_url( __FILE__ ).'images/subscribed.gif' ?>" />
                    </span>
	                <span v-else>
		                <h1 class="news-letter-title">Newsletter Signup</h1>
		                <input type="text" class="news-letter-input" name="" placeholder="What's your name?" autofocus v-model="name">
		                <input type="text" class="news-letter-input" name="" placeholder="What's your Email?" autofocus v-model="email">
		                <button class="news-letter-button" v-on:click="subscribe">Subscribe Now</button>
		            </span>
		        </form>
		    </span>
		</span>
		<?php
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		$apiKey = !empty( $instance['apiKey'] ) ? $instance['apiKey'] : esc_html__( 'MailChimp api key', 'text_domain' );
		$listId = !empty( $instance['listId'] ) ? $instance['listId'] : esc_html__( 'MailChimp list id', 'text_domain' );
		?>
		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'apiKey' ) ); ?>"><?php esc_attr_e( 'MailChimp api key:', 'text_domain' ); ?></label>
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'apiKey' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'apiKey' ) ); ?>" type="text" value="<?php echo esc_attr( $apiKey ); ?>">
		</p>
		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'listId' ) ); ?>"><?php esc_attr_e( 'MailChimp list id:', 'text_domain' ); ?></label>
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'listId' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'listId' ) ); ?>" type="text" value="<?php echo esc_attr( $listId ); ?>">
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['apiKey'] = ( !empty( $new_instance['apiKey'] ) ) ? strip_tags( $new_instance['apiKey'] ) : '';
		$instance['listId'] = ( !empty( $new_instance['listId'] ) ) ? strip_tags( $new_instance['listId'] ) : '';

		return $instance;
	}
}
