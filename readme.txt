=== HWCOE Ph.D. Students on the Job Market ===
Contributors: Allison Logan

Allows admin to display a dynamic list of entries using the Graduate Student custom_post_type.

== Description ==

The HWCOE Graduate Students on the Job Market plugin has been created specifically for websites using the hwcoe-ufl or hwcoe-ufl-child themes. This plugin allows admin to display a dynamic list of entries using the Ph.D. Student custom_post_type, Gravity Forms  using the Ph.D. Students on the Job Market form, the Gravity Forms + Custom Post Types plugin and Advanced Custom Fields (ACF) using the Ph.D. Students on the Job Market Modules field group. 

The specified custom_post_type, Gravity Form and ACF Field Group must be used for this plugin to work. 

== Required Plugins ==

Advanced Custom Fields
Gravity Forms
Gravity Forms + Custom Post Types

== Installation ==

1. Ensure all required plugins are installed and activated. 
2. Optional: Move the "_Required Files" folder to outside the plugin folder. (This folder does not need to be uploaded to your Plugins folder)
3. Upload the plugin to the Wordpress Plugins folder. 
4. Activate the plugin from the Plugins dashboard.
5. Go to Import/Export under the Gravity Forms Plugin.
6. Import the "gravityform-phdonjobmkt.json" file located in the "_Required Files" folder.
7. Set up the reCAPTCHA for your form: (If you haven't set up reCAPTCHA before, follow the below directions)
     - Go to the reCAPTCHA link (https://www.google.com/recaptcha/admin/create) to register your site. You may need to create a gmail account for this. Use something generic that can stay with the department and not something personal.
     - Select reCAPTCHA v2 and the first "I'm not a robot" option
     - Add your website address under domain
     - Accept the terms and submit. This should provide you with two keys you will need for the form.	
     - Back in Wordpress, go to Form > Settings. Scroll down to reCAPTCHA and put in your keys.
8. Set up the email notifications. 
     - Go to Forms > Ph.D. Students on the Job Market > Settings > Notifications
     - Turn on the Admin Notification and click to edit
     - Add in whatever email you would like to receive notifications of form submissions in the "Send To Email" field
9. Set up the Confirmation message
     - Go to Forms > Ph.D. Students on the Job Market > Settings > Confirmations
     - Select the Default Confirmation
     - Make the necessary changes
10. Paste the form shortcode on a page on your website to display the form.
11. Paste the plugin shortcode -- [graduate-student-job-listing] -- on the page you would like to display the table. 
     ***Page must have "Ph.D. Students on the Job Market" as the title.***
12. As entries are submitted, they will be pending approval in the Ph.D. Students tab and will display on the designated page once you have published the entry.


== Changelog ==

= v1.0 (09-08-2021) =
   * [NEW] Initial release
