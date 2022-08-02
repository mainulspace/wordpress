<?php
// Ninja form data submission hook
add_action('ninja_forms_after_submission', 'my_ninja_forms_after_submission');
function my_ninja_forms_after_submission($form_data)
{
    // Do stuff.
    $sms_text = 'New Seller:';
    $data_array = array();
    $seller_full_name = '';
    $first_name = '';
    $last_name = '';
    $email_address = '';
    $mobile_number = '';
    $post_id = '';
    $twilio_number = '';
    $sms_text_customer = 'Truck Fleet Buyer has received your request and will respond in 30 Minutes';
    $recipients_number = array();
    $required_field_array = array('post_id' => 'hidden_1576570951855', 'first_name' => 'first_name_1567054061360', 'last_name' => 'last_name_1564555555354', 'email_address' => 'email_address_1576570897266', 'mobile_number' => 'mobile_number_1576570856651');
    if (count($form_data['fields']) > 0) {
        foreach ($form_data['fields'] as $key => $field) { // Field settigns, including the field key and value.
            if (in_array($field['key'], $required_field_array)) {
                $key = array_search($field['key'], $required_field_array);
                $data_array[$key] = $field['value'];
            } else {
                continue;
            }
        }
        extract($data_array);
        if ($first_name && $last_name) {
            $seller_full_name = $first_name . ' ' . $last_name;
        } elseif ($first_name) {
            $seller_full_name = $first_name;
        } elseif ($last_name) {
            $seller_full_name = $last_name;
        }
        if ($seller_full_name) {
            $sms_text .= $seller_full_name . '/';
        }
        if ($mobile_number) {
            $sms_text .= $mobile_number . '/';
        }
        if ($email_address) {
            $sms_text .= $email_address;
        }
    }

    $account_sid = '';
    $auth_token = '';
    // In production, these should be environment variables. E.g.:
    // $auth_token = $_ENV["TWILIO_ACCOUNT_SID"]
    if ($post_id) {
        $twilio_number = get_field('sender_number', $post_id);
        $recipients_number_array = get_field('recipients_phone_number', $post_id);
        $recipients_number = explode(',', $recipients_number_array);
    }

    $client = new Client($account_sid, $auth_token);
    if ($twilio_number) {
        // Send sms to customer
        $client->messages->create(
        // Where to send a text message (your cell phone?)
            $mobile_number,
            array(
                'from' => $twilio_number,
                'body' => $sms_text_customer
            )
        );
        if (count($recipients_number) > 0) {
            // Send to user recipient
            foreach ($recipients_number as $key => $recipient) {
//                $recipient_object = 'reciepient_'.$key;
//                $recipient_object = new Client($account_sid, $auth_token);
//                    $recipient_object->messages->create(
                $client->messages->create(
                // Where to send a text message (your cell phone?)
                    $recipient,
                    array(
                        'from' => $twilio_number,
                        'body' => $sms_text
                    )
                );
            }// endforeach
        }//endif

    }
}