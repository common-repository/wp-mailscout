<?php

use MS\Services\Router;

// mail accounts
Router::get( 'mail_accounts', 'MailAccountController@index' );
Router::post( 'mail_accounts', 'MailAccountController@store' );
Router::post( 'mail_accounts/google-credentials', 'MailAccountController@updateGoogleCredentials' );
Router::post( 'mail_accounts/deleteMailAccount', 'MailAccountController@deleteMailAccount' );

// dashboard
Router::get( 'dashboard/google-credentials', 'DashboardController@getGoogleCredentials' );
Router::get( 'dashboard/get-all-info', 'DashboardController@getAllInfo' );
Router::get( 'dashboard/check-cron-status', 'DashboardController@checkCronStatus' );

// campaigns
Router::get( 'campaigns', 'CampaignController@index' );
Router::post( 'campaigns', 'CampaignController@store' );
Router::get( 'campaigns/campaign', 'CampaignController@view' );
Router::post( 'campaigns/campaign', 'CampaignController@update' );
Router::post( 'campaigns/deleteCampaign', 'CampaignController@deleteCampaign' );
Router::get( 'getUpdatedSubsGroupLists', 'CampaignController@updatedSubscriberLists' );

// Subscriber Groups
Router::get( 'SubscriberGroups', 'SubscriberGroupController@index' );
Router::post( 'SubscriberGroups', 'SubscriberGroupController@store' );
Router::post( 'SubscriberGroups/updateSubscriberList', 'SubscriberGroupController@updateSubscriberList' );

// subscribers
Router::get( 'subscribers', 'SubscriberController@index' );
Router::post( 'subscribers', 'SubscriberController@store' );
Router::get( 'subscribers/getSubscriberGroup', 'SubscriberController@getSubscriberGroup' );
Router::get( 'subscribers/getSubscriberGroupLists', 'SubscriberController@getSubscriberGroupLists' );
Router::get( 'subscribers/pullAllSubscribers', 'SubscriberController@pullAllSubscribers' );
Router::get( 'subscribers/allSubscribersCount', 'SubscriberController@allSubscribersCount' );
Router::get( 'paginatedSubscribers', 'SubscriberController@paginatedSubscribers' );
Router::post( 'subscribers/deleteGroup', 'SubscriberController@deleteGroup' );
Router::post( 'subscribers/deleteSingleSub', 'SubscriberController@deleteSingleSub' );

// compose
Router::post( 'compose', 'CampaignMessageController@store' );
Router::get( 'messages', 'CampaignMessageController@view' );

// message queue
Router::get( 'get_subs_grp_id', 'MessageQueueController@getSubsGrpId' );
Router::get( 'get_subs_grp_lists', 'MessageQueueController@getSubscriberGroupLists' );
Router::get( 'message_queue', 'MessageQueueController@view' );

//options
Router::post( 'compose/options', 'OptionsController@update' );

// settings
Router::get( 'settings/get-campaign-message-list', 'SettingsController@getAllCampaignMessage' );
Router::get( 'get_plugin_version', 'SettingsController@getPluginVersion' );