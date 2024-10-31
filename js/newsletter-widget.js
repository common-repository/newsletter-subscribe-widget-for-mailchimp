jQuery(document).ready(function($) {
    var NewsletterWidget = new Vue({
        el: '#newsletterWidget',
        data: {
            name: null,
            email: null, 
            subscribed: false,
            hidewidget: window.localStorage.getItem("codingStories_subscription")
        },
        methods: {
            subscribe: function () {
                var vueInstance = this;
                var data = {
		            'action': 'newsletter_widget',
		            'name': this.name,
		            'email': this.email,
		            'security': ajax_object.ajax_nonce
	            };
                jQuery.post(ajax_object.ajaxurl, data, function(response) {
                    
                    var jsonResponse = JSON.parse(response);
		            if(jsonResponse.success)
		            {
		                vueInstance.subscribed = true;
		                window.localStorage.setItem("codingStories_subscription", true)
		                swal(
                            'Thanks!',
                            'You`ve successfully subscribed to our newsletter!',
                            'success'
                        );
		            }
		            else
		            {
		                if(jsonResponse.error)
		                {
		                    swal(
                                'Oops...',
                                jsonResponse.error,
                                'error'
                            );
		                }
		                else
		                {
		                    swal(
                                'Oops...',
                                'Something went wrong!',
                                'error'
                            );
		                }
		            }
	            });
	            return false;
            }
        }
    });
});
