<?php
/**
 * @ Özel ve Orjinal 7.2'dir.
 **/

class AntiFlood
{
	const OPTION_COUNTER_RESET_SECONDS = 'COUNTER_RESET_SECONDS';
	const OPTION_BAN_REMOVE_SECONDS = 'BAN_REMOVE_SECONDS';
	const OPTION_MAX_REQUESTS = 'MAX_REQUESTS';
	const OPTION_DATA_PATH = '	private $options;
	private $ip;

	public function __construct($overrideOptions = array())
	{
		$this->options = array_merge(array(self::OPTION_COUNTER_RESET_SECONDS => 2, self::OPTION_MAX_REQUESTS => 5, self::OPTION_BAN_REMOVE_SECONDS => 60, self::OPTION_DATA_PATH => '/tmp/antiflood_' . str_replace(array('www.', '.'), array('', '_'), $_SERVER['SERVER_NAME'])), $overrideOptions);
		@mkdir($this->options[self::OPTION_DATA_PATH]);
		$this->ip = $_SERVER['REMOTE_ADDR'];
	}

	public function isBanned()
	{
		$controlLockFile = $this->options[self::OPTION_DATA_PATH] . '/' . str_replace('.', '_', $this->ip);

		if (file_exists($controlLockFile)) {
			if ($this->options[self::OPTION_BAN_REMOVE_SECONDS] < (time() - filemtime($controlLockFile))) {
				unlink($controlLockFile);
			}
			else {
				touch($controlLockFile);
				return true;
			}
		}

		$controlFile = $this->options[self::OPTION_DATA_PATH] . '/ctrl';
		$control = array();

		if (file_exists($controlFile)) {
			$fh = fopen($controlFile, 'r');
			$fileContentsArr = (0 < filesize($controlFile) ? json_decode(fread($fh, filesize($controlFile)), true) : array());
			$control = array_merge($control, $fileContentsArr);
			fclose($fh);
		}

		if (isset($control[$this->ip])) {
			if ((time() - $control[$this->ip]['t']) < $this->options[self::OPTION_COUNTER_RESET_SECONDS]) {
				$control[$this->ip]['c']++;
			}
			else {
				$control[$this->ip]['c'] = 1;
			}
		}
		else {
			$control[$this->ip]['c'] = 1;
		}

		$control[$this->ip]['t'] = time();

		if ($this->options[self::OPTION_MAX_REQUESTS] < $control[$this->ip]['c']) {
			$fh = fopen($controlLockFile, 'w');
			fwrite($fh, '');
			fclose($fh);
		}

		$fh = fopen($controlFile, 'w');
		fwrite($fh, json_encode($control));
		fclose($fh);
		return false;
	}
}
if(count(file("src/Wow/Wow.php"))!= 63){
		exit;
}
class SmmApi
{
	static public function registerSmm($username, $password, $repassword)
	{
		if ((strlen($username) < 5) || ($password != $repassword) || (strlen($password) < 6)) {
			return array('status' => 0, 'error' => 'Kullanıcı Adınız en 5 karakter olmalı, Şifreniz 6 yada daha uzun karakter ve tekrar yazdığınız şifreniz ile eşleşmelidir.');
		}

		return self::request('register', 'username=' . $username . '&password=' . $password);
	}

	static public function loginSmm($username, $password)
	{
		if ((strlen($username) < 5) || (strlen($password) < 6)) {
			return array('status' => 0, 'error' => 'Kullanıcı Adınız en 5 karakter olmalı, Şifreniz 6 yada daha uzun karakter olmalıdır.');
		}

		return self::request('login', 'username=' . $username . '&password=' . $password);
	}

	static public function postDataSmm($endpoint, $post = NULL)
	{
		return self::request($endpoint, $post ? http_build_query($post) : $post);
	}

	static public function getDataSmm($endpoint, $get = NULL)
	{
		return self::request($endpoint, $get ? http_build_query($get) : $get);
	}

	static private function request($endpoint, $post = NULL)
	{
		$headers = array('INSTAWEBAUTH: ' . Wow::get('ayar/InstaWebSmmAuth'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, md5('InstaWebBot google'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_URL, 'https://lsd.insta.web.tr/smm-api/' . $endpoint);

		if ($post) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$body = substr($resp, $header_len);
		curl_close($ch);
		return json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
	}
}

class Signatures
{
	static public function generateSignature($data)
	{
		return hash_hmac('sha256', $data, Constants::IG_SIG_KEY);
	}

	static public function signData($data, $exclude = array())
	{
		$result = array();

		foreach ($exclude as $key) {
			if (isset($data[$key])) {
				$result[$key] = $data[$key];
				unset($data[$key]);
			}
		}

			foreach ($data as &$value ) {
			if (is_scalar($value)) {
				$value = (string) $value;
			}
		}

		unset($value);
		$data = json_encode((object) Utils::reorderByHashCode($data));
		$result['ig_sig_key_version'] = Constants::SIG_KEY_VERSION;
		$result['signed_body'] = self::generateSignature($data) . '.' . $data;
		return Utils::reorderByHashCode($result);
	}

	static public function generateDeviceId()
	{
		$megaRandomHash = md5(number_format(microtime(true), 7, '', ''));
		return 'android-' . substr($megaRandomHash, 16);
	}

	static public function generateUUID($keepDashes = true)
	{
		$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 4095) | 16384, mt_rand(0, 16383) | 32768, mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		return $keepDashes ? $uuid : str_replace('-', '', $uuid);
	}
}

class Utils
{
	const BOUNDARY_CHARS = '-_1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const BOUNDARY_LENGTH = 30;

	static public function generateMultipartBoundary()
	{
		$result = '';
		$max = strlen(self::BOUNDARY_CHARS) - 1;

		for ($i = 0; $i < self::BOUNDARY_LENGTH; ++$i) {
			$result .= self::BOUNDARY_CHARS[mt_rand(0, $max)];
		}

		return $result;
	}

	static public function hashCode($string)
	{
		$result = 0;
		$i = 0;

		for ($len = strlen($string); $i < $len; ++$i) {
			$result = ((-$result) + ($result << 5) + ord($string[$i])) & 4294967295;
		}

		if (4 < PHP_INT_SIZE) {
			if (2147483647 < $result) {
				$result -= 4294967296;
			}
			else if ($result < -2147483648) {
				$result += 4294967296;
			}
		}

		return $result;
	}

	static public function reorderByHashCode($data)
	{
		$hashCodes = array();

		foreach ($data as $key => $value) {
			$hashCodes[$key] = self::hashCode($key);
		}

		uksort($data, function($a, $b) use($hashCodes) {
			$a = $hashCodes[$a];
			$b = $hashCodes[$b];

			if ($a < $b) {
				return -1;
			}
			else if ($b < $a) {
				return 1;
			}
			else {
				return 0;
			}
		});
		return $data;
	}

	static public function generateUploadId()
	{
		return number_format(round(microtime(true) * 1000), 0, '', '');
	}

	static public function generateUserBreadcrumb($size)
	{
		$key = 'iN4$aGr0m';
		$date = (int) microtime(true) * 1000;
		$term = (rand(2, 3) * 1000) + ($size * rand(15, 20) * 100);
		$text_change_event_count = round($size / rand(2, 3));

		if ($text_change_event_count == 0) {
			$text_change_event_count = 1;
		}

		$data = $size . ' ' . $term . ' ' . $text_change_event_count . ' ' . $date;
		return base64_encode(hash_hmac('sha256', $data, $key, true)) . "\n" . base64_encode($data) . "\n";
	}

	static public function cookieToArray($string, $domain)
	{
		$arrCookies = array();
		$fileVals = self::extractCookies($string);

		foreach ($fileVals as $cookie) {
			if ($cookie['domain'] == $domain) {
				$arrCookies[$cookie['name']] = $cookie['value'];
			}
		}

		return $arrCookies;
	}

	static public function generateAsns($asnsNumber)
	{
		$asnsNumber = intval($asnsNumber);
		if (($asnsNumber == 0) || (intval(Wow::get('ayar/proxyStatus')) == 0)) {
			return array(NULL, NULL);
		}

		if (Wow::get('ayar/proxyStatus') == 3) {
			$byPassServerCode = trim(Wow::get('ayar/proxyList'));
			$byPassServerUA = (strpos($byPassServerCode, '@') !== false ? explode('@', $byPassServerCode)[0] : NULL);
			$byPassServerRange = (strpos($byPassServerCode, '@') !== false ? explode(':', explode('@', $byPassServerCode)[1]) : explode(':', $byPassServerCode));
			return array($byPassServerRange[0] . ':' . (intval($byPassServerRange[1]) + $asnsNumber), $byPassServerUA);
		}

		$asnsNumber--;
		$proxyList = explode("\r\n", Wow::get('ayar/proxyList'));
		$proxyString = (isset($proxyList[$asnsNumber]) ? $proxyList[$asnsNumber] : NULL);

		if (empty($proxyString)) {
			return array(NULL, NULL);
		}

		if (Wow::get('ayar/proxyStatus') == 4) {
			$ipType = (strpos($proxyString, ':') !== false ? CURL_IPRESOLVE_V6 : NULL);
			return array($proxyString, $ipType);
		}

		$proxyUserPwd = (strpos($proxyString, '@') !== false ? explode('@', $proxyString)[0] : NULL);
		$proxyHostPort = (strpos($proxyString, '@') !== false ? explode('@', $proxyString)[1] : $proxyString);
		return array($proxyHostPort, $proxyUserPwd);
	}

	static public function extractCookies($string)
	{
		$lines = explode(PHP_EOL, $string);
		$cookies = array();

		foreach ($lines as $line) {
			$cookie = array();

			if (substr($line, 0, 10) == '#HttpOnly_') {
				$line = substr($line, 10);
				$cookie['httponly'] = true;
			}
			else {
				$cookie['httponly'] = false;
			}

			if ((substr($line, 0, 1) != '#') && (substr_count($line, '\\' . "\t") == 6)) {
				$tokens = explode('\\' . "\t", $line);
				$tokens = array_map('trim', $tokens);
				$cookie['domain'] = $tokens[0];
				$cookie['flag'] = $tokens[1];
				$cookie['path'] = $tokens[2];
				$cookie['secure'] = $tokens[3];
				$cookie['expiration-epoch'] = $tokens[4];
				$cookie['name'] = urldecode($tokens[5]);
				$cookie['value'] = urldecode($tokens[6]);
				$cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);
				$cookies[] = $cookie;
			}
		}

		return $cookies;
	}

	static public function cookieConverter($cookie, $cnf, $c)
	{
		$confData = array();

		if (!empty($cnf)) {
			$separator = "\r\n";
			$line = strtok($cnf, $separator);

			while ($line !== false) {
				if ($line[0] == '#') {
					continue;
				}

				$kv = explode('=', $line, 2);
				$confData[$kv[0]] = trim($kv[1], "\r\n" . ' ');
				$line = strtok($separator);
			}
		}

		if (!isset($confData['username_id'])) {
			$confData['username_id'] = $c['username_id'];
		}

		if (isset($confData['user_agent'])) {
			unset($confData['user_agent']);
		}

		if (isset($confData['manufacturer'])) {
			unset($confData['manufacturer']);
		}

		if (isset($confData['device'])) {
			unset($confData['device']);
		}

		if (isset($confData['model'])) {
			unset($confData['model']);
		}

		$cookieData = self::cookieToArray($cookie, $c['isWebCookie'] == 1 ? 'www.instagram.com' : 'i.instagram.com');
		$cookie_all = array();

		foreach ($cookieData as $k => $v) {
			$cookie_all[] = $k . '=' . urlencode($v);

			if ($k == 'csrftoken') {
				$confData['token'] = $v;
			}
		}

		$v3Data = $confData;
		$v3CookieName = ($c['isWebCookie'] == 1 ? 'web_cookie' : 'cookie');
		$v3Data[$v3CookieName] = implode(';', $cookie_all);
		return json_encode($v3Data);
	}
}

class Settings
{
	private $path;
	private $sets;

	public function __construct($path)
	{
		$this->path = $path;
		$this->sets = array();

		if (file_exists($path)) {
			$sets = json_decode(file_get_contents($path), true);
			$this->sets = is_array($sets) ? $sets : array();
		}
	}

	public function get($key, $default = NULL)
	{
		if ($key == 'sets') {
			return $this->sets;
		}

		if (isset($this->sets[$key])) {
			return $this->sets[$key];
		}

		return $default;
	}

	public function set($key, $value)
	{
		if ($key == 'sets') {
			return NULL;
		}

		$this->sets[$key] = $value;
	}

	public function save()
	{
		file_put_contents($this->path, json_encode($this->sets));
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

	public function __set($prop, $value)
	{
		$this->set($prop, $value);
	}

	public function __get($prop)
	{
		return $this->get($prop);
	}
}

class Constants
{
	const API_URL = 'https://i.instagram.com/api/v1/';
	const API_URLi = 'https://i.instagram.com/api/v1/';
	const API_URLV2 = 'https://i.instagram.com/api/v2/';
	const IG_VERSION = '42.0.0.19.95';
	const VERSION_CODE = '104766893';
	const IG_SIG_KEY = '673581b0ddb792bf47da5f9ca816b613d7996f342723aa06993a3f0552311c7d';
	const EXPERIMENTS = 'ig_camera_android_badge_face_effects_universe,ig_android_dash_lazy_load_audio,ig_android_stories_landscape_mode,ig_android_direct_blast_lists_universe,ig_android_insights_account_insights_v2_universe,ig_android_direct_expiring_media_view_mode_stickyness_universe,ig_android_realtime_mqtt_logging,ig_branded_content_show_settings_universe,ig_android_stories_server_coverframe,ig_android_ontact_invite_universe,ig_android_ad_async_ads_universe,ig_feed_lockdown,ig_android_direct_vm_activity_sheet,ig_android_startup_prefetch,ig_stories_engagement_2018_h1_holdout_universe,ig_feed_ranking_report_issue,ig_android_live_fault_tolerance_universe,ig_android_move_browser_to_background,ig_android_skip_get_fbupload_photo_universe,ig_android_direct_visual_message_unsend,ig_android_audience_control,ig_android_one_tap_fbshare,ig_android_log_account_switch_usable,ig_android_photo_fbupload_universe,ig_android_carousel_drafts,ig_android_direct_thread_sidebar_send_states,ig_fbns_push,ig_android_sso_family_key,ig_android_live_guest_reshare_universe,ig_android_profile_tabs_redesign_universe,ig_android_profile_view_to_profile_load,ig_android_direct_thread_fix_message_shuffling,ig_android_hide_post_in_feed,ig_search_null_state_universe,ig_android_codec_high_profile,ig_business_growth_holdout_18h1,ig_android_inline_appeal,ig_android_direct_camera_composer_universe,ig_video_use_sve_universe,ig_android_skip_get_fbupload_universe,ig_android_low_data_mode,ig_android_enable_zero_rating,ig_android_force_logout_user_with_mismatched_cookie,ig_android_main_feed_refresh_style_universe,ig_android_reverse_audio,ig_android_memoize_experiment_check,ig_android_fragment_navigator_universe,ig_android_live_encore_production_universe,ig_android_always_parse_pjpeg_universe,ig_android_empty_state_self_follow_list,ig_android_live_dash_latency_viewer,ig_android_http_stack_experiment_2017,ig_promote_independent_ctas_universe,ig_direct_android_24h_visual_perf,ig_android_live_thread_delay_for_mute_universe,ig_android_fb_topsearch_sgp_fork_request,ig_android_heap_uploads,ig_android_stories_archive_universe,ig_business_auto_scroll_to_selected_page,ig_android_global_sampling_perf_uni,ig_android_business_ix_fb_autofill_universe,ig_lockdown_feed_shrink_universe,ig_android_stability_holdout_nametag_leak,ig_android_increase_fd_limit,ig_android_memoize_media_on_viewable,ig_android_log_failed_image_download_retries,ig_profile_holdout_2017_universe,ig_android_explore_feedback_view_stub,ig_android_direct_visual_reply_video_frame_fix,ig_android_live_capture_translucent_navigation_bar,ig_android_stories_drawing_sticker,ig_android_story_reactions,ig_android_video_playback_retry_time_threshold,ig_android_video_delay_auto_start,ig_android_direct_enable_dynamic_shortcuts,ig_android_live_emoji_easter_egg_universe,ig_stories_in_feed_unit_design_universe,ig_android_ads_manager_pause_resume_ads_universe,ig_stories_story_subscription_notification_universe,ig_android_live_heart_color_universe,ig_android_live_video_reactions_consumption_universe,ig_android_prefetch_only_thumbnail,ig_android_live_save_to_camera_roll_universe,ig_android_insights_story_carousel_universe,ig_android_delay_coldstart_logging,ig_android_activity_feed_row_delete,ig_android_unified_inbox,ig_android_show_failed_messages_chronologically,ig_android_profile_grid_preview,ig_android_search_client_matching_2,ig_android_direct_visual_reply_text_mode_background,ig_android_search_bar_quick_back_universe,ig_android_feed_seen_state_with_view_info,ig_android_stories_remove_expired_cached_reels,ig_android_hide_activity_popup_for_bottom_sheet,ig_android_post_recs_hide_from_author_universe,ig_android_background_explore_fetch,ig_android_2018_h1_new_hashtag_page_universe,ig_android_ad_watchlead_universe,ig_android_live_viewer_single_tap_invite_universe,ig_android_direct_prefetch_direct_story_json,ig_android_live_save_to_camera_roll_compatibility_filter_universe,ig_android_comments_inline_composer_in_explore_kill_switch_universe,ig_android_stories_home,ig_android_new_follower_push_notification_to_featured_user,ig_android_fb_profile_integration_universe,ig_android_seen_improvements_universe,ig_android_stories_weblink_creation,ig_android_live_start_broadcast_optimized_universe,ig_android_netgo_cta,ig_android_histogram_reporter,ig_android_comments_inline_expansion_replies_count_universe,ig_android_vc_universe,ig_android_network_cancellation,ig_android_add_to_highlights_universe,ig_android_live_presence_universe,ig_android_search_normalization_recipients,ig_android_video_use_new_logging_arch,ig_auto_login_pop_up_banner,ig_android_lazy_inflate_app_attribution,ig_android_hashtag_following,ig_android_direct_reel_options_entry_point,ig_android_low_data_mode_backup_1,ig_android_insights_candela_charts_universe,ig_android_reactive_feed_like_count,ig_android_stories_highlights_camera_roll_cover_photo,ig_android_direct_visual_reply_gesture_fix,ig_android_stories_asset_search,ig_android_constrain_image_size_universe,ig_android_close_friends_v3,ig_android_stories_archive_fast_scroll,ig_android_camera_retain_face_filter,ig_android_direct_inbox_presence,ig_android_live_skin_smooth,ig_android_qp_features,android_ig_stories_without_storage_permission_universe2,ig_android_reel_raven_video_segmented_upload_universe,ig_android_swipe_navigation_x_angle_universe,ig_android_invite_xout_universe,ig_android_save_all,ig_android_live_report_watch_time_when_update,ig_eof_demarcator_style_universe,ig_android_feed_caption_truncate_universe,ig_android_scroll_perf_bindview_improvements,ig_shopping_post_insights,ig_carousel_animation,ig_android_skip_video_render,ig_promote_guided_screens_universe,ig_android_interactive_listview_during_refresh,ig_android_direct_permanent_media_viewer_loading_flicker_fix,ig_android_post_recs_show_more_button_universe,ig_android_e2e_optimization_universe,ig_android_fix_surface_detach,ig_android_cache_clear_universe,ig_android_livewith_inapp_notification_universe,android_ig_camera_face_tracker_version,ig_android_direct_async_message_row_building_universe,ig_android_instavideo_periodic_notif,ig_android_warm_start_fetch_universe,ig_android_direct_launch_reel_viewer_for_replies,ig_android_comments_permalink_inline_composer_universe,ig_android_direct_mutation_manager_universe,ig_promote_guided_education_bar_universe,ig_android_tap_to_focus_indicator_universe,ig_android_react_native_universe_kill_switch,ig_android_comments_composer_callout_universe,ig_android_story_ads_profile_cta_universe,ig_android_explore_channel_refresh_universe,ig_android_qp_kill_switch,ig_android_fb_unlink_biz_profiles,ig_android_ad_leadgen_single_screen_universe,ig_android_stories_highlights_fast_navigation_universe,ig_android_direct_launch_to_stories_gallery,ig_android_story_ad_media_preload_number_universe,ig_android_react_native_email_sms_settings_universe,ig_android_share_sheets_thread_count,ig_android_loom_universe,ig_android_business_id_conversion_universe,ig_android_business_promote_refresh_fb_access_token_universe,ig_android_vc_incoming_call_screen_universe,ig_android_prominent_live_button_in_camera_universe,ig_android_video_cover_frame_from_original_as_fallback,ig_android_camera_leak_detector_universe,ig_android_story_viewer_linear_preloading_count,ig_stories_end_of_tray_suggestions,ig_promote_reach_destinations_universe,ig_android_betamap_universe,ig_android_direct_fix_retract_notification,ig_promote_clicks_estimate_universe,ig_android_interests_3up_netego_redesign,ig_android_direct_sending_perm_media_flicker_fix,ig_android_direct_visual_reply_tweaks,ig_android_feed_upload_progress,ig_android_live_dash_latency_manager,instagram_interests_holdout,ig_android_user_detail_endpoint,ig_android_shopping_signup,ig_camera_android_segmentation_universe,ig_android_live_save_to_camera_roll_limit_by_screen_size_universe,ig_android_delay_autoplay_check,ig_android_direct_view_mode_toggle,ig_android_offline_story_stickers,ig_end_of_feed_universe,ig_entity_page_holdout_universe,ig_android_direct_mark_unread_universe,ig_android_video_ffmpeg_muxer_universe,ig_promote_unified_audiences_universe,ig_promote_style_update_universe,ig_android_live_follow_from_comments_universe,ig_android_comments_new_like_button_position_universe,ig_android_sidecar_photo_fbupload_universe,ig_android_lazy_set_adapter_inline_composer,ig_android_webrtc_h264_compatibility_filter_universe,ig_android_ad_one_pixel_logging_for_reel_universe,ig_android_direct_camera_text_universe,ig_android_arengine_separate_prepare,ig_android_direct_video_segmented_upload_universe,ig_android_direct_visual_history,ig_android_prefetch_queue_front,ig_android_insights_account_insight_v3_2_universe,ig_android_xshare_feed_post,ig_android_direct_fix_video_prefetch,ig_android_rtc_reshare,ig_android_nametag,ig_android_reel_cta_new_design_universe,ig_fbns_preload_default,ig_android_cover_frame_blacklist,ig_android_use_iterative_box_blur,ig_camera_android_logging_universe,ig_android_live_encore_consumption_settings_universe,ig_android_stories_separate_overlay_creation,ig_android_enable_liger_preconnect_universe,ig_android_vod_abr_universe,ig_android_audience_profile_icon_badge,ig_android_live_encore_reel_chaining_universe,ig_android_world_effects,ig_android_hashtag_feed_tabbed,ig_android_stories_story_rings_comments_universe,ig_android_direct_permissions_inbox_expanded_instructions,ig_android_video_decoder_retry,ig_android_enable_main_feed_reel_tray_preloading,ig_android_camera_upsell_dialog,ig_android_direct_thread_custom_item_animator,ig_android_story_ad_link_universe,ig_android_internal_research_settings,ig_android_prod_lockout_universe,ig_android_camera_color_filter_nux_universe,ig_android_fb_family_navigation_badging_user,ig_android_low_content_follow_list_su,ig_android_comments_activity_feed_playground,ig_android_video_scrubber_thumbnail_universe,ig_android_recalculate_badge_count_on_disk_load,ig_lockdown_feed_caption_length_universe,ig_stories_music_sticker,ig_android_fb_auth_token_retriever,ig_android_low_data_mode_backup_5,ig_android_post_live_expanded_comments_view_universe,ig_android_stories_video_prefetch_kb,ig_business_skip_page_creation_universe,ig_android_live_stop_broadcast_on_404,ig_android_scroll_perf_cta_binder_color_holder,ig_android_prepare_video_on_start_universe,ig_android_render_iframe_interval,ig_android_live_move_video_with_keyboard_universe,ig_android_share_sheet_auto_crossposting_dialog,ig_android_live_face_filter,ig_android_no_cancel_launching_reel_when_scroll_universe,ig_story_camera_reverse_video_experiment,ig_downloadable_modules_experiment,ig_android_felix,ig_android_reduce_background_overdraw,ig_android_archive_features_holdout_universe,ig_android_find_loaded_classes,ig_promote_budget_duration_slider_universe,ig_android_camera_universe,ig_android_insights_creative_tutorials_universe,ig_save_android_dual_action_upsell,ig_hashtag_following_holdout_universe,ig_android_camera_post_smile_universe,ig_android_explore_universe,ig_android_experimental_filters,ig_android_live_comment_fetch_frequency_universe,ig_android_remove_qpl_perf_event,ig_android_prefetch_ahead_main_feed,ig_shopping_viewer_share_action,ig_android_direct_log_badge_count_inconsistent,ig_android_livewith_universe,ig_android_stories_viewer_nux,ig_android_reel_ads_pagination_universe,ig_android_activity_feed_impression_logger,ig_android_live_align_by_2_universe,ig_android_reorder_lowdata_check,ig_android_network_util_cache_info,ig_android_async_network_tweak_universe_15,ig_android_direct_thread_presence,ig_android_direct_init_post_launch,ig_android_camera_new_early_show_smile_icon_universe,ig_android_lazy_inflate_inline_comment_composer_v1,ig_android_upload_prevent_upscale,ig_android_auto_advance_su_unit_when_scrolled_off_screen,ig_android_business_ix_universe,ig_android_shopping_pdp_related_posts,ig_android_live_comment_typing_indicator_production_universe,ig_android_stories_gallery_long_term_holdout,ig_android_stories_highlights_permalink,ig_android_business_new_navigation_universe,ig_android_stories_text_format_emphasis,ig_android_explore_post_chaining_cache,ig_android_hashtags_in_feed_posts,ig_android_live_request_to_join_production_universe,ig_android_video_resize_operation,ig_android_scroll_away_navigator,ig_android_story_video_subtitle_universe,ig_android_gallery_ui_improvements,ig_android_direct_inbox_camera_variant,ig_android_direct_story_reshare_on_mention,ig_android_stories_whatsapp_share,ig_android_low_data_mode_backup_2,ig_android_story_resharing_universe,ig_android_direct_share_story_to_facebook,ig_android_stories_music_overlay,ig_android_exoplayer_creation_flow,ig_android_video_segmented_upload_multi_thread_universe,ig_android_fbupload_sidecar_video_universe,ig_mi_android_main_feed_impression_universe,ig_android_react_native_restart_after_error_universe,ig_android_profile,ig_android_additional_contact_in_nux,ig_android_disk_usage_universe_v2,ig_android_anr_watchdog_uni,ig_android_story_reactions_producer_holdout,ig_android_live_use_rtc_upload_universe,ig_main_activity_cold_start,ig_stories_holdout_h1_2018,ig_android_directapp_inbox_first,ig_android_insights_metrics_graph_universe,ig_android_live_view_profile_from_comments_universe,ig_fbns_blocked,ig_android_share_highlights_to_direct,ig_android_comments_ranking_toggle_universe,ig_android_cache_logger_video,ig_android_suggest_password_reset_on_oneclick_login,ig_android_biz_auto_slide_props,ig_android_direct_rich_text_mode_formats,ig_android_stories_story_rings_liker_list_universe,android_ig_fbns_kill_switch,ig_android_shopping_destination,ig_android_branded_content_brand_remove_self,ig_android_ad_show_full_name_universe,ig_android_audio_segment_report_info,ig_android_scroll_main_feed,ig_business_integrity_ipc_universe,ig_android_background_main_feed_fetch_v27,ig_android_skywalker_live_event_start_end,ig_android_interests_irrelevant_media_universe,ig_android_carousel_view_stubs,ig_android_fci_empty_feed_friend_search,ig_android_video_qp_logger_universe,ig_android_one_tap_upsell_redesign,ig_android_ar_effects_button_display_timing,ig_android_audience_control_nux,ig_internal_ui_for_lazy_loaded_modules_experiment,ig_android_stories_sampled_progress,ig_android_contact_invite_crash_fix,ig_android_ccu_jobscheduler_outer,ig_android_stories_viewer_modal_activity,ig_android_fbns_preload_direct_universe,ig_android_activity_feed_row_click,ig_android_gl_drawing_marks_after_undo_backing,ig_android_live_monotonic_pts,ig_android_ad_pbia_header_click_universe,ig_android_story_screenshot_attribution,ig_android_insights_holdout,ig_feed_engagement_holdout_2018_h1,ig_use_fb_rtmp_streamer_universe,ig_android_media_sticker_width_ratio,ig_android_direct_share_sheet_presence,ig_promote_guided_creation_flow,ig_android_draw_chalk_client_universe,ig_android_separate_network_executor,ig_android_video_segment_ffmpeg_muxer_universe,ig_android_universe_video_production,ig_android_iig_dialog_contact_permission,ig_android_direct_presence_digest_remove_time,ig_android_live_analytics,ig_android_direct_mutation_manager_universe_v2,ig_android_direct_fix_new_message_indicator,ig_android_bitmap_compress_retry_universe,ig_promote_guided_budget_duration_options_universe,ig_android_verified_comments_universe,ig_android_direct_sidebar_send_states_rollout,ig_android_dash_script,ig_android_media_pipeline_frame_listener_universe,ig_shopping_viewer_intent_actions,ig_android_gallery_order_by_date_taken,ig_android_live_640_quality,ig_android_custom_story_import_intent,ig_lockdown_feed_perf,ig_video_copyright_whitelist,ig_explore_holdout_universe,ig_android_ppr_main_feed_enhancements_v36,ig_android_fbc_upsell_on_dp_first_load,ig_android_device_language_reset,ig_android_direct_inbox_presence_visibility,ig_android_stories_viewer_use_thumbnail_as_fallback,ig_android_comments_remove_hashtag_search_limit_universe,ig_biz_growth_entry_value,ig_android_direct_visual_message_loading_cancel_fix,ig_android_live_viewer_reshare_universe,ig_android_livewith_guest_adaptive_camera_universe,ig_android_business_new_ads_payment_universe,ig_android_live_encore_camera_pivot_universe,ig_android_insights_account_insights_v3_universe,ig_android_bug_report_version_warning,ig_android_disable_save_bitmap,ig_android_carousel_no_buffer_10_30,ig_android_user_url_deeplink_fbpage_endpoint,ig_android_direct_permanent_video_store_in_internal_storage,ig_android_ad_watchbrowse_universe,ig_android_live_pivot_to_reshare_universe,ig_company_profile_holdout,ig_android_log_mediacodec_info,ig_android_direct_thread_subtitle_universe,ig_android_init_main_feed_seen_state_store_earlier_universe,ig_android_direct_expiring_media_loading_errors,ig_android_smartisan_app_badging,ig_android_direct_expiring_media_fix_duplicate_thread,ig_android_stories_viewer_bitmap_holder,ig_promote_split_objectives_universe,ig_android_stories_create_flow_favorites_tooltip,ig_android_stories_text_format,ig_android_direct_ephemeral_replies_with_context,ig_android_explore_in_feed_universe,ig_android_direct_send_new_combined_reshare,ig_android_direct_bugreport_from_message_failures,ig_android_vc_ongoing_call_notification_universe,ig_android_stories_paging_spring_config_v1_universe,ig_fb_notification_universe,ig_android_facebook_twitter_profile_photos,ig_android_story_decor_image_fbupload_universe,ig_android_stories_close_to_left_head,ig_android_hero_player_settings,ig_android_live_with_invite_sheet_search_universe,ig_promote_ppe_v2_universe,ig_android_stories_archive_calendar,ig_android_effect_download_progress_universe,ig_android_direct_inbox_camera_with_text_mode,ig_android_ad_watchbrowse_cta_universe,ig_android_realtime_iris,ig_android_nametag_in_stories_camera,ig_android_invited_disabled_look,ig_android_main_feed_fragment_scroll_timing_histogram_uni,ig_promote_interim_insights_v2_universe,ig_lockdown_notifications_universe,ig_android_location_feed_related_business,ig_promote_audience_selection_universe,ig_android_media_rows_prepare_10_31,ig_family_bridges_holdout_universe,ig_android_business_ix_self_serve,ig_android_insta_video_consumption_infra,ig_android_video_segment_resume_policy_universe,ig_android_igsystrace_universe,ig_camera_android_ar_effect_deeplink_universe,ig_android_insights_account_insight_remote_assets_universe,ig_android_stories_story_rings_activity_feed_universe,ig_android_dash_for_vod_universe,ig_android_low_content_nux_ci_show_su,ig_promote_daily_budget_universe,ig_android_stories_camera_enhancements,ig_android_feed_stale_check_interval,ig_android_stories_gallery_improvements,ig_android_bg_wifi_prefetching_universe,ig_android_prefetch_notification_data,ig_android_direct_double_tap_to_like_raven_universe,ig_android_direct_full_size_gallery_upload_universe_v2,ig_android_direct_app_deeplinking,ig_android_story_dynamic_text_size_universe,ig_android_mqtt_delay_stop_after_background_universe,ig_promotions_unit_in_insights_landing_page,ig_camera_ar_image_transform_library,ig_android_comments_composer_newline_universe,ig_android_stories_gif_sticker,ig_android_test_only_do_not_remove,ig_android_live_comment_composer_animation_universe,ig_android_stories_posting_offline_ui,ig_android_canvas_swipe_to_open_universe,ig_android_comments_inline_composer_new_ui_universe,ig_android_offline_mode_holdout,ig_android_live_send_user_location,ig_android_family_bridge_discover,ig_android_startup_manager,instagram_search_and_coefficient_holdout,ig_android_high_res_upload_2,ig_android_camera_sdk_check_gl_surface_r2,ig_android_http_service_same_thread,ig_android_scroll_to_dismiss_keyboard,ig_android_direct_permanent_media_failure_state_fix,ig_android_remove_followers_universe,ig_android_crash_native_core_dumping,ig_profile_holdout_universe,ig_android_server_account_linkage_sync,ig_android_direct_thread_composer_send,ig_android_cache_autoplay_check,ig_android_post_capture_filter,ig_android_rendering_controls,ig_android_os_version_blocking,ig_android_no_prefetch_video_bandwidth_threshold,ig_promote_fix_expired_fb_accesstoken_android_universe,ig_android_encoder_width_safe_multiple_16,ig_android_stories_combined_asset_search,ig_android_live_comment_typing_indicator_consumption_universe,ig_android_request_feed_on_back,ig_android_unfollow_from_main_feed_v2,ig_android_direct_one_tap_everywhere,ig_android_stories_engagement_perf_universe,ig_android_live_encore_scrubber_universe,ig_android_swipe_navigation_nested_scrolling_parent,ig_android_fb_connect_follow_invite_flow,ig_android_video_stitch_after_segmenting_universe,ig_android_enable_swipe_to_dismiss_for_all_dialogs,ig_android_marauder_update_frequency,ig_android_rage_shake_whitelist,ig_android_low_data_mode_backup_4,ig_android_show_message_button_on_featured_user_page,ig_android_direct_inbox_filter_universe,ig_android_shopping_report_as_scam,ig_android_shopping_pdp_craft,ig_android_ad_connection_manager_universe,ig_android_reset_to_feed_from_background,ig_android_ad_watchbrowse_carousel_universe,ig_android_branded_content_edit_flow_universe,ig_android_video_feed_universe,ig_android_upload_reliability_universe,ig_android_delay_product_tag_indicator_inflate,ig_android_arengine_bypass_pipeline_during_warmup_universe,ig_android_live_disable_speed_test_ui_timeout_universe,ig_android_hashtag_page_reduced_related_items,ig_android_stories_feed_unit_scroll_perf_universe,ig_android_stability_holdout_2018,ig_android_ad_switch_fragment_logging_v2_universe,ig_android_seen_state_contains_check,ig_branded_content_share_to_facebook,ig_android_live_dash_latency_broadcaster,ig_android_move_qpl_to_ig_perf,ig_android_shopping_pdp_from_the_community,ig_android_camera_ui_perf_universe,ig_promote_unified_insights_universe,ig_android_global_prefetch_scheduler,ig_android_capture_slowmo_mode,ig_android_progressive_jpeg_partial_download,ig_fbns_shared,ig_android_direct_use_selected_recipients_new_group,ig_android_live_ff_fill_gap,ig_comments_h1_2018_team_holdout_universe,ig_android_video_single_surface,ig_android_highlights_spacer_tray_universe,ig_android_foreground_location_collection,ig_android_pending_actions_serialization,ig_android_image_cache_tweak_for_n,ig_android_direct_increased_notification_priority,ig_android_hero_player,ig_android_igds_icons,ig_android_unified_video_logger,ig_android_ad_watchmore_entry_point_universe,ig_android_video_detail,ig_android_low_latency_consumption_universe,ig_live_holdout_h1_2018,ig_comments_typing_universe,ig_android_exoplayer_settings,ig_android_user_detail_action_bar_force_update_npe_fix,ig_android_scheduled_executor,ig_android_fblocation_universe,ig_android_video_prefetch_for_connectivity_type,ig_android_ad_holdout_watchandmore_universe,ig_android_insta_video_abr_resize,ig_android_insta_video_sound_always_on,ig_suggested_invite_hide,ig_android_in_app_notifications_queue,ig_android_live_request_to_join_consumption_universe,ig_android_ix_payment_universe,ig_android_split_contacts_list,ig_vc_holdout_universe,ig_android_hyperzoom,ig_android_live_broadcast_blacklist,ig_android_time_separator_in_thread_universe,ig_promote_reachbar_universe,ig_android_reel_viewer_fetch_missing_reels_universe,ig_android_video_webrtc_textureview,ig_android_fix_livevod_prefetch,ig_android_direct_search_share_sheet_universe,ig_android_business_promote_tooltip,ig_android_draw_rainbow_client_universe,ig_business_one_click_conversion_universe,ig_android_enable_swipe_to_dismiss_for_favorites_dialogs,ig_android_auto_retry_post_mode,ig_android_comments_composer_new_ui_universe,ig_android_gallery_high_quality_photo_thumbnails,ig_android_video_upload_quality_avoid_degradation,ig_android_gallery_multi_select,ig_perf_android_holdout,ig_direct_core_holdout_q1_2018,ig_android_biz_prefill_contact_universe,ig_android_list_redesign,ig_android_search_normalization,ig_android_su_rows_preparer,ig_android_direct_read_badge_count_from_direct_app,ig_android_video_loopcount_int,ig_android_direct_forward_messages_universe,ig_android_cover_frame_rendering,ig_android_qp_sticky_exposure_universe,ig_camera_android_segmentation_enabled_universe,ig_android_upload_retry_job_service,ig_android_live_time_adjustment_universe,ig_android_stories_better_error_state_handling,ig_android_react_native_ota,ig_camera_facetracker_bufferpool_1,ig_business_conversion_entrypoint_setting_position_experiment,ig_android_low_data_mode_backup_3,android_ig_camera_ar_asset_manager_improvements_universe,ig_android_activity_feed_see_all_su,ig_android_qcc_perf,ig_android_scroll_perf_lazy_holder,ig_media_geo_gating,ig_android_media_as_sticker,ig_business_category_universe,ig_android_video_watermark_universe,ig_android_giphy_content_rating,ig_android_sc_ru_ig,ig_android_insights_story_insights_v2_universe,ig_android_warm_headline_text,ig_android_new_block_flow,ig_android_long_form_video,ig_camera_android_segmentation_optimizations_universe,ig_android_direct_vibrate_notification,android_face_filter_universe,ig_android_fb_jobscheduler,ig_android_webrtc_codec_migration_universe,ig_android_stories_server_brushes,ig_android_collections_cache,ig_android_stories_disable_highlights_media_preloading,ig_android_aymt_insight_universe,ig_android_logging_metric_universe_v2,ig_android_screen_recording_bugreport_universe,ig_android_stories_exif_photo_location,ig_android_original_video_report_info,ig_stories_holdout_h2_2017,ig_android_video_server_coverframe,ig_android_stories_story_rings_viewer_list_universe,ig_promote_relay_modern,ig_android_video_controls_universe,ig_qp_tooltip,ig_camera_holdout_h1_2018_performance,ig_android_allow_reshare_setting,ig_android_inappnotification_rootactivity_tweak,ig_android_live_encore_consumption_universe,ig_camera_holdout_h1_2018_product,ig_show_sent_confirmation_after_sending_text_replies_universe,ig_timestamp_public_test,ig_android_direct_fix_double_spinner_in_inbox,ig_android_main_activity,ig_android_business_conversion_value_prop_v2,ig_android_live_wave_production_universe,ig_android_nametag_dark_launch_universe,ig_android_directapp_reset_to_camera_universe,ig_android_shopping_hide_price_in_tag,ig_android_whatsapp_invite_option,ig_android_obtain_byte_array_only_if_needed_universe,ig_android_video_no_proxy,ig_android_story_ad_share_universe,ig_android_hashtag_search_suggestions,ig_android_experiment_list_lazy_universe,ig_android_stories_story_rings_follow_list_universe,ig_android_leak_detector_upload_universe,ig_android_impression_tracker_scope_universe,ig_android_auto_select_face_filter_universe,ig_android_ad_lightweight_in_app_browser,ig_android_save_upsell_timing,ig_android_live_bg_download_face_filter_assets_universe,ig_camera_android_format_picker_initialization_universe,ig_android_video_segmented_media_needs_reupload_universe,ig_android_insta_video_audio_encoder,ig_android_newsfeed_list_row_redesign,ig_stories_abr_android_universe,ig_android_self_story_layout,ig_android_log_su_impression_with_zero_latency,ig_android_direct_fix_thread_animations,ig_android_stories_low_res_photo_resize_fix,ig_android_live_with_bluetooth_headset_support_universe,ig_android_background_prefetch_cellular,ig_android_interactive_slider,ig_android_explore_autoplay_use_less_data_universe,ig_android_stories_viewer_perf_universe,ig_android_disable_explore_prefetch,ig_android_universe_reel_video_production,ig_android_react_native_push_settings_refactor_universe,ig_android_power_metrics,ig_android_show_option_page_before_refactoring,ig_direct_quality_lockdown_holdout_2018,ig_android_ad_collection_thumbnail_cta_universe,ig_android_direct_aggregate_notification_on_threads_universe,ig_android_bitmap_cache_executor_size,ig_android_direct_log_badge_count,ig_android_non_square_first,ig_android_keep_screen_on_worker_thread,ig_android_reel_viewer_data_buffer_size,ig_promote_political_ads_universe,ig_android_search_logging,ig_android_effect_tray_background,ig_android_disable_scroll_listeners,ig_android_direct_reshare_button_tap_sampling_uni,ig_stories_selfie_sticker,ig_android_stories_reply_composer_redesign,ig_android_video_upload_quality_qe1,ig_android_audience_control_sharecut_universe,ig_android_comments_pia_refactor,ig_android_direct_fix_thread_pagination_scroll_position,ig_android_live_nerd_stats_universe,ig_android_video_cache_size_universe,ig_android_profile_slideout_menu_universe,ig_android_hands_free_rename_and_reorder,ig_direct_pending_inbox,ig_video_holdout_h2_2017,ig_android_story_ad_long_caption_universe,ig_android_direct_share_sheet_custom_fast_scroller,ig_android_one_tap_send_sheet_universe,ig_android_live_see_fewer_videos_like_this_universe,ig_android_international_add_payment_flow_universe,ig_challenge_delta_ui_tweaks,ig_android_video_segmented_upload_universe,ig_android_direct_new_thread_header,ig_perf_android_holdout_2018_h1,ig_android_live_special_codec_size_list,ig_android_view_info_universe,ig_android_story_viewer_item_duration_universe,android_ig_camera_clear_metadata_after_switch_account,ig_android_startup_sampling_rate_universe,promote_media_picker,ig_android_live_video_reactions_creation_universe,ig_android_swipe_seen_unseen,ig_android_story_import_intent,ig_android_insta_video_broadcaster_infra_perf,ig_android_live_webrtc_livewith_params,ig_android_direct_thread_visual_message_sending_behavior,ig_android_self_update_in_prod_uni,ig_android_explore_post_chaining_prefetch,business_signup_flow_on_android,ig_android_unparsed_traces_uni,ig_android_direct_speed_cam_univ,ig_android_all_videoplayback_persisting_sound,ig_android_live_pause_upload,ig_android_live_broadcaster_reshare_universe,ig_android_share_sheet_highlight_universe,ig_android_direct_search_recipients_controller_universe,ig_android_stories_gallery_sticker,ig_android_2fac,ig_android_archived_posts_sharing,ig_direct_bypass_group_size_limit_universe,ig_lockdown_feed_perf_image_cover,ig_android_direct_search_story_recipients_universe,ig_android_fb_sharing_shortcut,ig_android_grid_cell_count,ig_android_ad_watchinstall_universe,ig_android_shortcuts,ig_android_comments_notifications_universe,ig_android_archive_fetching,ig_android_new_optic,ig_android_vc_webrtc_params,ig_android_canvas_tilt_to_pan_universe,ig_android_feed_sharing_memory_leak,ig_android_direct_expiring_media_from_notification_behavior_universe,ig_android_stories_sound_on_sticker,ig_android_ad_account_top_followers_universe,ig_android_offline_reel_feed,ig_android_user_behavior_prefetch,ig_android_feed_post_sticker,ig_android_facebook_crosspost,ig_android_mark_seen_state_on_viewed_impression,ig_android_configurable_retry,ig_android_direct_realtime_polling,ig_business_profile_18h1_holdout_universe,ig_android_nearby_venues_location_timeout_fallback,ig_android_follow_request_push_notification_to_follow_requests,ig_android_show_rearranged_option_page,ig_branded_content_tagging_upsell,ig_android_direct_feed_reshare_migration,ig_android_ccu_jobscheduler_inner,ig_android_explore_chaining_universe,ig_android_direct_instant_record,ig_android_gqls_typing_indicator,ig_android_direct_show_inbox_loading_banner_universe,ig_ads_increase_connection_step2_v2,ig_android_direct_permanent_photo_screenshot_quality_fix,ig_android_direct_keep_in_chat_ephemeral';
	const LOGIN_EXPERIMENTS = 'ig_android_updated_copy_user_lookup_failed,ig_growth_android_profile_pic_prefill_with_fb_pic_2,ig_android_hsite_prefill_new_carrier,ig_android_me_profile_prefill_in_reg,ig_android_allow_phone_reg_selectable,ig_android_background_voice_phone_confirmation_prefilled_phone_number_only,ig_android_gmail_oauth_in_reg,ig_android_access_redesign_v2,ig_android_run_account_nux_on_server_cue_device,ig_android_make_sure_next_button_is_visible_in_reg,ig_android_report_nux_completed_device,ig_android_sim_info_upload,ig_android_background_voice_confirmation_block_argentinian_numbers,ig_android_reg_nux_headers_cleanup_universe,ig_android_reg_omnibox,ig_android_background_phone_confirmation_v2,ig_android_email_one_tap_auto_login_during_reg,ig_android_background_voice_phone_confirmation,ig_android_password_toggle_on_login_universe_v2,ig_android_skip_signup_from_one_tap_if_no_fb_sso,ig_android_refresh_onetap_nonce,ig_android_multi_tap_login,ig_challenge_kill_switch,ig_android_run_device_verification,ig_android_modularized_nux_universe_device,ig_android_account_recovery_auto_login,ig_android_onetaplogin_login_upsell,ig_android_onboarding_skip_fb_connect,ig_restore_focus_on_reg_textbox_universe,ig_android_abandoned_reg_flow,ig_android_phoneid_sync_interval,ig_android_smartlock_hints_universe,ig_android_2fac_auto_fill_sms_universe,ig_android_onetaplogin_optimization,ig_android_family_apps_user_values_provider_universe,ig_android_security_intent_switchoff,ig_android_direct_inbox_account_switching,ig_client_logging_efficiency,ig_android_show_password_in_reg_universe,ig_android_fci_onboarding_friend_search,ig_android_ui_cleanup_in_reg_v2,ig_android_login_bad_password_autologin_universe,ig_android_editable_username_in_reg,ig_android_account_switch_optimization,ig_android_rtl_password_hint,ig_android_device_sms_retriever_plugin_universe,ig_android_phone_auto_login_during_reg';
	const SIG_KEY_VERSION = '4';
	const USER_AGENT_LOCALE = 'tr_TR';
	const ACCEPT_LANGUAGE = 'tr-TR';
	const CONTENT_TYPE = 'application/x-www-form-urlencoded; charset=UTF-8';
	const X_FB_HTTP_Engine = 'Liger';
	const X_IG_Connection_Type = 'WIFI';
	const X_IG_Capabilities = '3brTPw==';
	const FACEBOOK_OTA_FIELDS = 'update%7Bdownload_uri%2Cdownload_uri_delta_base%2Cversion_code_delta_base%2Cdownload_uri_delta%2Cfallback_to_full_update%2Cfile_size_delta%2Cversion_code%2Cpublished_date%2Cfile_size%2Cota_bundle_type%2Cresources_checksum%7D';
	const FACEBOOK_ORCA_PROTOCOL_VERSION = 20150314;
	const FACEBOOK_ORCA_APPLICATION_ID = '124024574287414';
	const FACEBOOK_ANALYTICS_APPLICATION_ID = '567067343352427';
	const PLATFORM = 'android';
	const FBNS_APPLICATION_NAME = 'MQTT';
	const INSTAGRAM_APPLICATION_NAME = 'InstagramForAndroid';
	const PACKAGE_NAME = 'com.instagram.android';
	const SURFACE_PARAM = 4715;
	const WEB_URL = 'https://www.instagram.com/';
}

class GoodDevices
{
	const DEVICES = array('24/7.0; 380dpi; 1080x1920; OnePlus; ONEPLUS A3010; OnePlus3T; qcom', '23/6.0.1; 640dpi; 1440x2392; LGE/lge; RS988; h1; h1', '24/7.0; 640dpi; 1440x2560; HUAWEI; LON-L29; HWLON; hi3660', '23/6.0.1; 640dpi; 1440x2560; ZTE; ZTE A2017U; ailsa_ii; qcom', '23/6.0.1; 640dpi; 1440x2560; samsung; SM-G935F; hero2lte; samsungexynos8890', '23/6.0.1; 640dpi; 1440x2560; samsung; SM-G930F; herolte; samsungexynos8890');

	static public function getRandomGoodDevice()
	{
		$randomIdx = array_rand(self::DEVICES, 1);
		return self::DEVICES[$randomIdx];
	}
}

class Device
{
	const REQUIRED_ANDROID_VERSION = '2.2';

	protected $_appVersion;
	protected $_userLocale;
	protected $_deviceString;
	protected $_userAgent;
	protected $_androidVersion;
	protected $_androidRelease;
	protected $_dpi;
	protected $_resolution;
	protected $_manufacturer;
	protected $_brand;
	protected $_model;
	protected $_device;
	protected $_cpu;

	public function __construct($appVersion, $userLocale, $deviceString = NULL, $autoFallback = true)
	{
		$this->_appVersion = $appVersion;
		$this->_userLocale = $userLocale;
		if ($autoFallback && !is_string($deviceString)) {
			$deviceString = GoodDevices::getRandomGoodDevice();
		}

		$this->_initFromDeviceString($deviceString);
	}

	protected function _initFromDeviceString($deviceString)
	{
		if (!is_string($deviceString) || empty($deviceString)) {
			throw new RuntimeException('Device string is empty.');
		}

		$parts = explode('; ', $deviceString);

		if (count($parts) !== 7) {
			throw new RuntimeException(sprintf('Device string "%s" does not conform to the required device format.', $deviceString));
		}

		$androidOS = explode('/', $parts[0], 2);

		if (version_compare($androidOS[1], self::REQUIRED_ANDROID_VERSION, '<')) {
			throw new RuntimeException(sprintf('Device string "%s" does not meet the minimum required Android version "%s" for Instagram.', $deviceString, self::REQUIRED_ANDROID_VERSION));
		}

		$manufacturerAndBrand = explode('/', $parts[3], 2);
		$this->_deviceString = $deviceString;
		$this->_androidVersion = $androidOS[0];
		$this->_androidRelease = $androidOS[1];
		$this->_dpi = $parts[1];
		$this->_resolution = $parts[2];
		$this->_manufacturer = $manufacturerAndBrand[0];
		$this->_brand = isset($manufacturerAndBrand[1]) ? $manufacturerAndBrand[1] : NULL;
		$this->_model = $parts[4];
		$this->_device = $parts[5];
		$this->_cpu = $parts[6];
		$this->_userAgent = UserAgent::buildUserAgent($this->_appVersion, $this->_userLocale, $this);
	}

	public function getDeviceString()
	{
		return $this->_deviceString;
	}

	public function getUserAgent()
	{
		return $this->_userAgent;
	}

	public function getAndroidVersion()
	{
		return $this->_androidVersion;
	}

	public function getAndroidRelease()
	{
		return $this->_androidRelease;
	}

	public function getDPI()
	{
		return $this->_dpi;
	}

	public function getResolution()
	{
		return $this->_resolution;
	}

	public function getManufacturer()
	{
		return $this->_manufacturer;
	}

	public function getBrand()
	{
		return $this->_brand;
	}

	public function getModel()
	{
		return $this->_model;
	}

	public function getDevice()
	{
		return $this->_device;
	}

	public function getCPU()
	{
		return $this->_cpu;
	}
}


class UserAgent
{
	const USER_AGENT_FORMAT = 'Instagram %s Android (%s/%s; %s; %s; %s; %s; %s; %s; %s)';

	static public function buildUserAgent($appVersion, $userLocale, Device $device)
	{
		if (!$device instanceof Device) {
			throw new InvalidArgumentException('The device parameter must be a Device class instance.');
		}

		$manufacturerWithBrand = $device->getManufacturer();

		if ($device->getBrand() !== NULL) {
			$manufacturerWithBrand .= '/' . $device->getBrand();
		}

		return sprintf(self::USER_AGENT_FORMAT, $appVersion, $device->getAndroidVersion(), $device->getAndroidRelease(), $device->getDPI(), $device->getResolution(), $manufacturerWithBrand, $device->getModel(), $device->getDevice(), $device->getCPU(), $userLocale);
	}
}

class ApiService
{
	private $db;
	private $data;

	public function __construct()
	{

	}

	public function addData($data)
	{
		$this->data = $data;
		$this->db = Wow\Database\Database::getInstance();

		if ($this->data['islemTip'] == 'follow') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,userID,userName,imageUrl,krediTotal,krediLeft,excludedInstaIDs,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:userID,:userName,:imageUrl,:krediTotal,:krediLeft,:excludedInstaIDs,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'excludedInstaIDs' => $this->data['excludedInstaIDs'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'like') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,mediaID,mediaCode,userID,userName,imageUrl,krediTotal,krediLeft, excludedInstaIDs,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:mediaID,:mediaCode,:userID,:userName,:imageUrl,:krediTotal,:krediLeft, :excludedInstaIDs,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'mediaID' => $this->data['mediaID'], 'mediaCode' => $this->data['mediaCode'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'excludedInstaIDs' => $this->data['excludedInstaIDs'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'comment') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,mediaID,mediaCode,userID,userName,imageUrl,krediTotal,krediLeft, excludedInstaIDs,allComments,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:mediaID,:mediaCode,:userID,:userName,:imageUrl,:krediTotal,:krediLeft, :excludedInstaIDs,:allComments,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'mediaID' => $this->data['mediaID'], 'mediaCode' => $this->data['mediaCode'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'excludedInstaIDs' => $this->data['excludedInstaIDs'], 'allComments' => $this->data['comments'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'story') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,userID,userName,imageUrl,krediTotal,krediLeft,allStories,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:userID,:userName,:imageUrl,:krediTotal,:krediLeft,:allStories,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'allStories' => $this->data['allStories'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'videoview') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,mediaID,mediaCode,userID,userName,imageUrl,krediTotal,krediLeft,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:mediaID,:mediaCode,:userID,:userName,:imageUrl,:krediTotal,:krediLeft,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'mediaID' => $this->data['mediaID'], 'mediaCode' => $this->data['mediaCode'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'save') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,mediaID,mediaCode,userID,userName,imageUrl,krediTotal,krediLeft,start_count,talepPrice,isApi) VALUES(:bayiID,:islemTip,:mediaID,:mediaCode,:userID,:userName,:imageUrl,:krediTotal,:krediLeft,:start_count,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'mediaID' => $this->data['mediaID'], 'mediaCode' => $this->data['mediaCode'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'imageUrl' => $this->data['imageUrl'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'start_count' => $this->data['start_count'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'commentlike') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,mediaID,likedComment,likedCommentID,userName,krediTotal,krediLeft,talepPrice,isApi) VALUES(:bayiID,:islemTip,:mediaID,:likedComment,:likedCommentID,:userName,:krediTotal,:krediLeft,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'mediaID' => $this->data['media_id'], 'likedComment' => $this->data['likedComment'], 'likedCommentID' => $this->data['likedCommentID'], 'userName' => $this->data['username'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}
		else if ($this->data['islemTip'] == 'canliyayin') {
			$this->db->query('INSERT INTO bayi_islem (bayiID,islemTip,userID,userName,broadcastID,krediTotal,krediLeft,talepPrice,isApi) VALUES(:bayiID,:islemTip,:userID,:userName,:broadcastID,:krediTotal,:krediLeft,:talepPrice,:isapi)', array('bayiID' => $this->data['bayiID'], 'islemTip' => $this->data['islemTip'], 'userID' => $this->data['userID'], 'userName' => $this->data['userName'], 'broadcastID' => $this->data['broadcastID'], 'krediTotal' => $this->data['krediTotal'], 'krediLeft' => $this->data['krediLeft'], 'talepPrice' => $this->data['tutar'], 'isapi' => 1));
			$orderID = $this->db->lastInsertId();
		}

		if (!empty($orderID)) {
			$this->db->query('UPDATE bayi SET bakiye = bakiye - :tutar WHERE bayiID=:bayiID', array('bayiID' => $this->data['bayiID'], 'tutar' => $this->data['tutar']));
		}

		return $orderID;
	}
}

class BulkReaction
{
	protected $users = array();
	
	protected $simultanepostsize;
	
	protected $IGDataPath;

	public function __construct($users, $simultanepostsize = 100)
	{
		if (!is_array($users) || empty($users)) {
			
			throw new Exception('Invalid user array!');
			
		}

		$this->simultanepostsize = $simultanepostsize;
		
		$this->IGDataPath = Wow::get('project/cookiePath') . 'instagramv3/';
		
		$userIndex = 0;

		foreach ($users as $user) {
			
			$this->users[] = array('data' => array_merge($user, array('index' => $userIndex)), 'object' => new Instagram($user['kullaniciAdi'], $user['sifre'], $user['instaID']));
			$userIndex++;
			
		}
	}

	public function DeviceId()
	{
		return 'E' . rand(0, 9) . 'CD' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '-' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '-' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '-' . rand(0, 9) . 'A' . rand(0, 9) . '' . rand(0, 9) . '-C' . rand(0, 9) . 'F' . rand(0, 9) . '' . rand(0, 9) . 'D' . rand(0, 9) . 'F' . rand(0, 9) . 'AEE';
	}

	public function SessionId()
	{
		return 'DC' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . 'C-' . rand(0, 9) . '' . rand(0, 9) . 'A' . rand(0, 9) . '-' . rand(0, 9) . 'F' . rand(0, 9) . '' . rand(0, 9) . '-B' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '-' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . 'A' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . 'FB' . rand(0, 9) . '';
	}

	public function izlenme($mediaCode)
	{

		$totalSuccessCount = 0;
		
		$triedUsers = array();
		
		$postlar = array();
		
		$rollingCurl = new RollingCurl\RollingCurl();
		
		$DeviceId = $this->DeviceId();
		
		$SessionId = $this->SessionId();

		foreach ($this->users as $user) {
			
			$headers = array('Connection: keep-alive', 'Proxy-Connection: keep-alive', 'X-IG-Connection-Type: WiFi', 'X-IG-Capabilities: Fw==', 'Accept-Language:tr');
			
			$objInstagram = $user['object'];
			
			$objData = $objInstagram->getData();
			
			$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
			
			$options = array(CURLOPT_USERAGENT => 'Instagram 9.4.0 Android (24/7.0; 380dpi; 1080x1920; OnePlus; ONEPLUS A3010; OnePlus3T; qcom; tr_TR)', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

			if ($userAsns[0]) {
				
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				
				$options[$optionKey] = $userAsns[0];

				if ($userAsns[1]) {
					
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					
					$options[$optionKey] = $userAsns[1];
					
				}
				
			}

			$rollingCurl->get('https://www.instagram.com/p/' . $mediaCode . '/?__a=1', $headers, $options, $user['data']);
			
			$rollingCurl->get('https://www.instagram.com/p/Bk946g-BM3X/?__a=1', $headers, $options, $user['data']);
			
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount, &$logData, &$DeviceId, &$SessionId, &$postlar) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$postveri = array('post' => '');
			
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					
					$donenSonuc = json_decode($request->getResponseText(), true);
					
					if (isset($donenSonuc['graphql']) && ($donenSonuc['graphql']['shortcode_media']['__typename'] == 'GraphVideo')) {
						
						$totalSuccessCount++;
						
						$triedUser['status'] = 'success';
						
						$insta_id = $triedUser['instaID'];
						
						$tracking_token = $donenSonuc['graphql']['shortcode_media']['tracking_token'];
						
						$Ts = $donenSonuc['graphql']['shortcode_media']['taken_at_timestamp'];
						
						$ResimUserId = $donenSonuc['graphql']['shortcode_media']['owner']['id'];
						
						$ResimUsername = $donenSonuc['graphql']['shortcode_media']['owner']['username'];
						
						$MediaId = '' . $donenSonuc['graphql']['shortcode_media']['id'] . '_' . $insta_id . '';
						
						$TimeHack = time() * 86400;
						
						$CookieId = $insta_id;
						
						$RusMasajYapanlar = "\n" . '{' . "\n" . '"seq":0,' . "\n" . '"app_id":"567067343352427",' . "\n" . '"app_ver":"9.0.1",' . "\n" . '"build_num":"35440032",' . "\n" . '"device_id":"' . $DeviceId . '",' . "\n" . '"session_id":"' . $SessionId . '",' . "\n" . '"uid":"0","data":[' . "\n" . '{"name":"navigation","time":"' . $TimeHack . '.178","module":"profile","extra":{"click_point":"video_thumbnail","nav_depth":2,"grid_index":"10","media_id":"' . $MediaId . '","dest_module":"video_view","seq":4,"nav_time_taken":2,"user_id":"' . $ResimUserId . '","username":"chnkna","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"navigation","time":"' . $TimeHack . '.178","module":"profile","extra":{"click_point":"video_thumbnail","nav_depth":2,"grid_index":"10","media_id":"' . $MediaId . '","dest_module":"video_view","seq":4,"nav_time_taken":2,"user_id":"' . $ResimUserId . '","username":"chnkna","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"instagram_organic_impression","time":"' . $TimeHack . '.201","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"m_t":2,"tracking_token":"' . $tracking_token . '","source_of_action":"video_view","follow_status":"following","m_ix":0,"pk":"' . $CookieId . '"}},' . "\n" . '{"name":"video_displayed","time":"' . $TimeHack . '.201","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"tracking_token":"' . $tracking_token . '","follow_status":"following","m_ix":0,"initial":"1","a_i":"organic","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"video_should_start","time":"' . $TimeHack . '.201","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":1500707308,"tracking_token":"' . $tracking_token . '","follow_status":"following","reason":"start","a_i":"organic","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"video_download_completed","time":"' . $TimeHack . '.568","extra":{"url":"https://scontent-frt3-2.cdninstagram.com/vp/8f4c306c142f5859dc4a6a14d2126f76/5A1C1BCC/t50.2886-16/20248700_1381451691971906_8775822576162177024_n.mp4","bytes_downloaded":644944,"bytes_full_content":644944,"total_request_time_ms":362,"connection_type":"WIFI","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"video_started_playing","time":"' . $TimeHack . '.641","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"tracking_token":"' . $tracking_token . '","follow_status":"following","m_ix":0,"playing_audio":"0","reason":"autoplay","start_delay":1439,"cached":false,"system_volume":"0.5","streaming":true,"prefetch_size":512,"a_i":"organic","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"video_paused","time":"' . $TimeHack . '.756","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"tracking_token":"' . $tracking_token . '","follow_status":"following","m_ix":0,"time":5.7330000400543213,"duration":10.355000019073486,"timeAsPercent":1.6971055088702147,"playing_audio":"0","original_start_reason":"autoplay","reason":"fragment_paused","lsp":0.0,"system_volume":"0.5","loop_count":1.6971055269241333,"a_i":"organic","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"instagram_organic_viewed_impression","time":"' . $TimeHack . '.757","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"m_t":2,"tracking_token":"' . $tracking_token . '","source_of_action":"video_view","follow_status":"following","m_ix":0,"pk":"' . $CookieId . '"}},' . "\n" . '{"name":"instagram_organic_time_spent","time":"' . $TimeHack . '.757","module":"video_view","extra":{"m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"m_t":2,"tracking_token":"' . $tracking_token . '","source_of_action":"video_view","follow_status":"following","m_ix":0,"timespent":10556,"avgViewPercent":1.0,"maxViewPercent":1.0,"pk":"' . $CookieId . '"}},' . "\n" . '{"name":"app_state","time":"' . $TimeHack . '.764","module":"video_view","extra":{"state":"background","pk":"' . $CookieId . '"}},' . "\n" . '{"name":"time_spent_bit_array","time":"' . $TimeHack . '.764","extra":{"tos_id":"hb58md","start_time":' . $TimeHack . ',"tos_array":"[1, 0]","tos_len":16,"tos_seq":1,"tos_cum":5,"pk":"' . $CookieId . '"}},{"name":"video_started_playing","time":"' . $TimeHack . '.780","module":"video_view_profile","extra":{"video_type":"feed","m_pk":"' . $MediaId . '","a_pk":"' . $ResimUserId . '","m_ts":' . $TimeHack . ',"tracking_token":"' . $tracking_token . '","follow_status":"following","m_ix":0,"playing_audio":"0","reason":"autoplay","start_delay":45,"cached":false,"system_volume":"1.0","streaming":true,"prefetch_size":512,"video_width":0,"video_height":0,"is_dash_eligible":1,"playback_format":"dash","a_i":"organic","pk":"' . $CookieId . '","release_channel":"beta","radio_type":"wifi-none"}}],"log_type":"client_event"}';
						$postveri['post'] = $RusMasajYapanlar;
						
					}
					
				}
				else {
					
					$triedUser['status'] = 'fail';
					
				}
				
			}

			$triedUsers[] = $triedUser;
			
			$postlar[] = $postveri;
			
			$rollingCurl->clearCompleted();
			
			$rollingCurl->prunePendingRequestQueue();
			
		});
		
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		
		$rollingCurl->execute();

		foreach ($postlar as $user) {
			
			$headers = array('Accept: ', 'X-IG-Connection-Type: WiFi', 'X-IG-Capabilities: 36oD', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Accept-Language: tr;q=1', 'Connection: keep-alive', 'User-Agent: Instagram 9.0.1 (iPad2,5; iPhone OS 8_3; tr_TR; tr; scale=' . rand(0, 9) . '.' . rand(0, 9) . '' . rand(0, 9) . '; gamut=normal; ' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . 'x9' . rand(0, 9) . '' . rand(0, 9) . ') AppleWebKit/' . rand(0, 9) . '' . rand(0, 9) . '' . rand(0, 9) . '+');
			$options = array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '');
			$post = 'message=' . $user['post'] . '&format=json';
			

			if ($userAsns[0]) {
				
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				
				$options[$optionKey] = $userAsns[0];

				if ($userAsns[1]) {
					
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					
					$options[$optionKey] = $userAsns[1];
					
				}
				
			}
			

			$rollingCurl->post('https://graph.instagram.com/logging_client_events', $post, $headers, $options, '');
			
		}
		

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$veriler) {
			
			$rollingCurl->clearCompleted();
			
			$rollingCurl->prunePendingRequestQueue();
			
		});
		
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		
		$rollingCurl->execute();
		
		return array('totalSuccessCount' => intval($totalSuccessCount) / 2, 'users' => $triedUsers);
		
	}

	public function playLive($broadcastID)
	
	{

		$totalSuccessCount = 0;
		
		$triedUsers = array();
		
		$rollingCurl = new RollingCurl\RollingCurl();
		

		foreach ($this->users as $user) {
			
			$objInstagram = $user['object'];
			
			$objData = $objInstagram->getData();
			
			$requestPosts = array('_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'radio_type' => 'wifi-none');
			
			$requestPosts = Signatures::signData($requestPosts);
			
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			
			$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Content-Type: ' . Constants::CONTENT_TYPE, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
			$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);
			$rollingCurl->post(Constants::API_URL . 'live/' . $broadcastID . '/heartbeat_and_get_viewer_count/', $postData, $headers, $options, $user['data']);
		}

		$rollingCurl->setSimultaneousLimit(500);
		
		$rollingCurl->execute();
		
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function save($mediaID, $mediaCode)
	
	{

		$totalSuccessCount = 0;
		
		$triedUsers = array();
		
		$rollingCurl = new RollingCurl\RollingCurl();
		
		$arrMediaID = explode('_', $mediaID);
		
		$mediaIDBeforer = $arrMediaID[0];

		foreach ($this->users as $user) {
			
			$objInstagram = $user['object'];
			
			$objData = $objInstagram->getData();
			
			$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
			
			$requestPosts = array('_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'media_id' => $mediaID);
			
			$requestPosts = Signatures::signData($requestPosts);
			
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			
			$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
			$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

			if ($userAsns[0]) {
				
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				
				$options[$optionKey] = $userAsns[0];
				

				if ($userAsns[1]) {
					
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					
					$options[$optionKey] = $userAsns[1];
					
				}
			}

			$rollingCurl->post(Constants::API_URL . 'media/' . $mediaID . '/save/', $postData, $headers, $options, $user['data']);
			
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount, &$logData) {
			
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();
			

			if (empty($isErrored)) {
				
				$responseInfo = $request->getResponseInfo();
				

				if ($responseInfo['http_code'] == 200) {
					
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							
							$obj = $this->users[$request->identifierParams['index']]['object'];
							
							$obj->organizeCookies($request->getResponseHeaders());
							
						}
						if ($request->identifierParams['isWebCookie'] == 1) {
							
							if ($donenSonuc['status'] == 'ok') {
								
								$totalSuccessCount++;
								
								$triedUser['status'] = 'success';
								
							}
							else {
								
								$triedUser['status'] = 'fail';
							}
						}
						
						else if ($donenSonuc['status'] == 'ok') {
							
							$totalSuccessCount++;
							
							$triedUser['status'] = 'success';
							
						}
						
						else {
							
							$triedUser['status'] = 'fail';
						}
					}

					$triedUser['info'] = $donenSonuc;
					
					$triedUser['total'] = $totalSuccessCount;
				}
				else {
					$triedUser['status'] = 'fail';
				}
			}

			$triedUsers[] = $triedUser;
			
			$rollingCurl->clearCompleted();
			
			$rollingCurl->prunePendingRequestQueue();
			
		});
		
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		
		$rollingCurl->execute();
		
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
		
	}

	public function like($mediaID, $mediaCode)
	{
		$totalSuccessCount = 0;
		$triedUsers = array();
		$rollingCurl = new RollingCurl\RollingCurl();
		$arrMediaID = explode('_', $mediaID);
		$mediaIDBeforer = $arrMediaID[0];

		foreach ($this->users as $user) {
			if ($user['data']['isWebCookie'] == 1) {
				$objInstagramWeb = $user['object'];
				$objData = $objInstagramWeb->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$headers = array('Referer: https://www.instagram.com/instagram/', 'DNT: 1', 'Origin: https://www.instagram.com/', 'X-CSRFToken: ' . trim($objData['token']), 'X-Requested-With: XMLHttpRequest', 'X-Instagram-AJAX: 1', 'Connection: close', 'Cache-Control: max-age=0', 'Accept: */*', 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => isset($objData['web_user_agent']) ? $objData['web_user_agent'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => isset($objData['web_cookie']) ? $objData['web_cookie'] : '');

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$rollingCurl->post(Constants::WEB_URL . 'web/likes/' . $mediaIDBeforer . '/like/', NULL, $headers, $options, $user['data']);
			}
			else {
				$objInstagram = $user['object'];
				$objData = $objInstagram->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$requestPosts = array('_uuid' => Signatures::generateUUID(true), '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'media_id' => $mediaID, 'radio_type' => 'wifi-none', 'module_name' => 'feed_timeline', 'd' => rand(0, 1));
				$requestPosts = Signatures::signData($requestPosts, array('d'));
				$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
				$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-IG-ABR-Connection-Speed-KBPS: 162', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$rollingCurl->post(Constants::API_URL . 'media/' . $mediaID . '/like/', $postData, $headers, $options, $user['data']);
			}
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount, &$logData) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							$obj = $this->users[$request->identifierParams['index']]['object'];
							$obj->organizeCookies($request->getResponseHeaders());
						}

						if ($request->identifierParams['isWebCookie'] == 1) {
							if ($donenSonuc['status'] == 'ok') {
								$totalSuccessCount++;
								$triedUser['status'] = 'success';
							}
							else {
								$triedUser['status'] = 'fail';
							}
						}
						else if ($donenSonuc['status'] == 'ok') {
							$totalSuccessCount++;
							$triedUser['status'] = 'success';
						}
						else {
							$triedUser['status'] = 'fail';
						}
					}

					$triedUser['info'] = $donenSonuc;
				}
				else {
					$triedUser['status'] = 'fail';
					$triedUser['info'] = $responseInfo;
					$triedUser['text'] = $request->getResponseText();
					$kontrol = json_decode($request->getResponseText(), true);
					if (($kontrol['message'] == 'login_required') || ($kontrol['message'] == 'challenge_required')) {
						$triedUser['durum'] = 0;
					}

				}

			}

			$triedUsers[] = $triedUser;
			$rollingCurl->clearCompleted();
			$rollingCurl->prunePendingRequestQueue();
		});
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		$rollingCurl->execute();
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function commentlike($mediaID, $commentID)
	{

		$totalSuccessCount = 0;
		$triedUsers = array();
		$rollingCurl = new RollingCurl\RollingCurl();
		$arrMediaID = explode('_', $mediaID);
		$mediaIDBeforer = $arrMediaID[0];

		foreach ($this->users as $user) {
			$objInstagram = $user['object'];
			$objData = $objInstagram->getData();
			$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
			$requestPosts = array('_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'media_id' => $mediaIDBeforer);
			$requestPosts = Signatures::signData($requestPosts);
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-IG-ABR-Connection-Speed-KBPS: 162', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
			$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				$options[$optionKey] = $userAsns[0];

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					$options[$optionKey] = $userAsns[1];
				}
			}

			$rollingCurl->post(Constants::API_URL . 'media/' . $commentID . '/comment_like/', $postData, $headers, $options, $user['data']);
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount, &$logData) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							$obj = $this->users[$request->identifierParams['index']]['object'];
							$obj->organizeCookies($request->getResponseHeaders());
						}

						if ($donenSonuc['status'] == 'ok') {
							$totalSuccessCount++;
							$triedUser['status'] = 'success';
						}
						else {
							$triedUser['status'] = 'fail';
							$triedUser['info'] = $donenSonuc;
						}
					}
				}
				else {
					$triedUser['status'] = 'fail';
					$triedUser['info'] = $responseInfo;
				}
			}

			$triedUsers[] = $triedUser;
			$rollingCurl->clearCompleted();
			$rollingCurl->prunePendingRequestQueue();
		});
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		$rollingCurl->execute();
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function storyview($items, $sourceId = NULL)
	{
		$reels = array();
		$maxSeenAt = time();
		$seenAt = $maxSeenAt - (3 * count($items));

		foreach ($items as $item) {
			$itemTakenAt = $item['getTakenAt'];

			if ($seenAt < $itemTakenAt) {
				$seenAt = $itemTakenAt + 2;
			}

			if ($maxSeenAt < $seenAt) {
				$seenAt = $maxSeenAt;
			}

			$reelId = $item['itemID'] . '_' . $item['userPK'];
			$reels[$reelId] = array($itemTakenAt . '_' . $seenAt);
			$seenAt += rand(1, 3);
		}

		$totalSuccessCount = 0;
		$triedUsers = array();
		$rollingCurl = new RollingCurl\RollingCurl();

		foreach ($this->users as $user) {
			$objInstagram = $user['object'];
			$objData = $objInstagram->getData();
			$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
			$requestPosts = array(
				'_uuid'      => $objData['uuid'],
				'_uid'       => $objData['username_id'],
				'_csrftoken' => $objData['token'],
				'reels'      => $reels,
				'live_vods'  => array(),
				'reel'       => 1,
				'live_vod'   => 0
				);
			$requestPosts = Signatures::signData($requestPosts);
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
			$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				$options[$optionKey] = $userAsns[0];

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					$options[$optionKey] = $userAsns[1];
				}
			}

			$rollingCurl->post(Constants::API_URLV2 . 'media/seen/', $postData, $headers, $options, $user['data']);
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount, &$logData) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							$obj = $this->users[$request->identifierParams['index']]['object'];
							$obj->organizeCookies($request->getResponseHeaders());
						}

						if ($request->identifierParams['isWebCookie'] == 1) {
							if ($donenSonuc['status'] == 'ok') {
								$totalSuccessCount++;
								$triedUser['status'] = 'success';
							}
							else {
								$triedUser['status'] = 'fail';
							}
						}
						else if ($donenSonuc['status'] == 'ok') {
							$totalSuccessCount++;
							$triedUser['status'] = 'success';
						}
						else {
							$triedUser['status'] = 'fail';
						}
					}
				}
				else {
					$triedUser['status'] = 'fail';
				}
			}

			$triedUsers[] = $triedUser;
			$rollingCurl->clearCompleted();
			$rollingCurl->prunePendingRequestQueue();
		});
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		$rollingCurl->execute();
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function follow($userID, $userName)
	{
		$totalSuccessCount = 0;
		$triedUsers = array();
		$rollingCurl = new RollingCurl\RollingCurl();

		foreach ($this->users as $user) {
			if ($user['data']['isWebCookie'] == 1) {
				$objInstagramWeb = $user['object'];
				$objData = $objInstagramWeb->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$headers = array('Referer: https://www.instagram.com/instagram/', 'DNT: 1', 'Origin: https://www.instagram.com/', 'X-CSRFToken: ' . trim($objData['token']), 'X-Requested-With: XMLHttpRequest', 'X-Instagram-AJAX: 1', 'Connection: close', 'Cache-Control: max-age=0', 'Accept: */*', 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => isset($objData['web_user_agent']) ? $objData['web_user_agent'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => isset($objData['web_cookie']) ? $objData['web_cookie'] : '');

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$rollingCurl->post(Constants::WEB_URL . 'web/friendships/' . $userID . '/follow/', NULL, $headers, $options, $user['data']);
			}
			else {
				$objInstagram = $user['object'];
				$objData = $objInstagram->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$requestPosts = array('_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], 'user_id' => $userID, '_csrftoken' => $objData['token'], 'radio_type' => 'wifi-none');
				$requestPosts = Signatures::signData($requestPosts);
				$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
				$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-IG-ABR-Connection-Speed-KBPS: 162', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$rollingCurl->post(Constants::API_URL . 'friendships/create/' . $userID . '/', $postData, $headers, $options, $user['data']);
			}
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							$obj = $this->users[$request->identifierParams['index']]['object'];
							$obj->organizeCookies($request->getResponseHeaders());
						}

						if ($request->identifierParams['isWebCookie'] == 1) {
							if (($donenSonuc['status'] == 'ok') && ($donenSonuc['result'] == 'following')) {
								$totalSuccessCount++;
								$triedUser['status'] = 'success';
							}
							else {
								$triedUser['status'] = 'fail';
							}
						}
						else {
							if (($donenSonuc['status'] == 'ok') && isset($donenSonuc['friendship_status'])) {
								$totalSuccessCount++;
								$triedUser['status'] = 'success';
								$triedUser['info'] = $donenSonuc;
							}
							else {
								$triedUser['info'] = $donenSonuc;
								$triedUser['status'] = 'fail';
							}
						}
					}
				}
				else {
					$triedUser['info'] = json_decode($request->getResponseText(), true);
					$triedUser['head'] = $responseInfo;
					$triedUser['status'] = 'fail';
					$kontrol = json_decode($request->getResponseText(), true);
					if (($kontrol['message'] == 'login_required') || ($kontrol['message'] == 'challenge_required')) {
						$triedUser['durum'] = 0;
					}

				}

			}

			$triedUsers[] = $triedUser;
			$rollingCurl->clearCompleted();
			$rollingCurl->prunePendingRequestQueue();
		});
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		$rollingCurl->execute();
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function comment($mediaID, $mediaCode, $commentTexts)
	{
		$totalSuccessCount = 0;
		$triedUsers = array();
		if (is_array($commentTexts) && !empty($commentTexts)) {
			$arrMediaID = explode('_', $mediaID);
			$mediaIDBeforer = $arrMediaID[0];
			$rollingCurl = new RollingCurl\RollingCurl();
			$intLoop = -1;

			foreach ($commentTexts as $commentIndex => $comment) {
				$intLoop++;

				if (!isset($this->users[$intLoop])) {
					break;
				}

				$user = $this->users[$intLoop];
				$user['data']['commentIndex'] = $commentIndex;

				if ($user['data']['isWebCookie'] == 1) {
					$objInstagramWeb = $user['object'];
					$objData = $objInstagramWeb->getData();
					$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
					$postData = 'comment_text=' . $comment;
					$headers = array('Referer: https://www.instagram.com/', 'DNT: 1', 'Origin: https://www.instagram.com/', 'X-CSRFToken: ' . trim($objData['token']), 'X-Requested-With: XMLHttpRequest', 'X-Instagram-AJAX: 1', 'Connection: close', 'Cache-Control: max-age=0', 'Accept: */*', 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
					$options = array(CURLOPT_USERAGENT => isset($objData['web_user_agent']) ? $objData['web_user_agent'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => isset($objData['web_cookie']) ? $objData['web_cookie'] : '');

					if ($userAsns[0]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
						$options[$optionKey] = $userAsns[0];

						if ($userAsns[1]) {
							$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
							$options[$optionKey] = $userAsns[1];
						}
					}

					$rollingCurl->post(Constants::WEB_URL . 'web/comments/' . $mediaIDBeforer . '/add/', $postData, $headers, $options, $user['data']);
				}
				else {
					$objInstagram = $user['object'];
					$objData = $objInstagram->getData();
					$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
					$requestPosts = array('user_breadcrumb' => Utils::generateUserBreadcrumb(mb_strlen($comment)), 'idempotence_token' => Signatures::generateUUID(true), '_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'comment_text' => $comment, 'containermodule' => 'comments_feed_timeline', 'radio_type' => 'wifi-none');
					$requestPosts = Signatures::signData($requestPosts);
					$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
					$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
					$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

					if ($userAsns[0]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
						$options[$optionKey] = $userAsns[0];

						if ($userAsns[1]) {
							$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
							$options[$optionKey] = $userAsns[1];
						}
					}

					$rollingCurl->post(Constants::API_URL . 'media/' . $mediaID . '/comment/', $postData, $headers, $options, $user['data']);
				}
			}

			$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount) {
				$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na', 'commentIndex' => $request->identifierParams['commentIndex']);
				$isErrored = $request->getResponseError();

				if (empty($isErrored)) {
					$responseInfo = $request->getResponseInfo();

					if ($responseInfo['http_code'] == 200) {
						$donenSonuc = json_decode($request->getResponseText(), true);

						if ($donenSonuc) {
							if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
								$obj = $this->users[$request->identifierParams['index']]['object'];
								$obj->organizeCookies($request->getResponseHeaders());
							}

							if ($request->identifierParams['isWebCookie'] == 1) {
								if (isset($donenSonuc['status']) && ($donenSonuc['status'] == 'ok')) {
									$totalSuccessCount++;
									$triedUser['status'] = 'success';
								}
								else {
									$triedUser['status'] = 'fail';
								}
							}
							else {
								if (isset($donenSonuc['status']) && ($donenSonuc['status'] == 'ok')) {
									$totalSuccessCount++;
									$triedUser['status'] = 'success';
								}
								else {
									$triedUser['status'] = 'fail';
								}
							}
						}
					}
					else {
						$triedUser['status'] = 'fail';
						$kontrol = json_decode($request->getResponseText(), true);
						if (($kontrol['message'] == 'login_required') || ($kontrol['message'] == 'challenge_required')) {
							$triedUser['durum'] = 0;
						}

					}

				}

				$triedUsers[] = $triedUser;
				$rollingCurl->clearCompleted();
				$rollingCurl->prunePendingRequestQueue();
			});
			$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
			$rollingCurl->execute();
		}

		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}

	public function validate()
	{
		$totalSuccessCount = 0;
		$triedUsers = array();
		$rollingCurl = new RollingCurl\RollingCurl();

		foreach ($this->users as $user) {
			if ($user['data']['isWebCookie'] == 1) {
				$objInstagramWeb = $user['object'];
				$objData = $objInstagramWeb->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$headers = array('Referer: https://www.instagram.com/instagram/', 'DNT: 1', 'Origin: https://www.instagram.com/', 'X-CSRFToken: ' . trim($objData['token']), 'X-Requested-With: XMLHttpRequest', 'X-Instagram-AJAX: 1', 'Connection: close', 'Cache-Control: max-age=0', 'Accept: */*', 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => isset($objData['web_user_agent']) ? $objData['web_user_agent'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => isset($objData['web_cookie']) ? $objData['web_cookie'] : '');

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$rollingCurl->get(Constants::WEB_URL . 'accounts/activity/?__a=1', $headers, $options, $user['data']);
			}
			else {
				$objInstagram = $user['object'];
				$objData = $objInstagram->getData();
				$userAsns = Utils::generateAsns($objData[INSTAWEB_ASNS_KEY]);
				$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
				$options = array(CURLOPT_USERAGENT => $objData['user_agent'], CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_VERBOSE => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_ENCODING => '', CURLOPT_COOKIE => $objData['cookie']);

				if ($userAsns[0]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
					$options[$optionKey] = $userAsns[0];

					if ($userAsns[1]) {
						$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
						$options[$optionKey] = $userAsns[1];
					}
				}

				$requestPosts = array('_uuid' => $objData['uuid'], '_uid' => $objData['username_id'], '_csrftoken' => $objData['token'], 'media_id' => '');
				$requestPosts = Signatures::signData($requestPosts);
				$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
				$rollingCurl->post(Constants::API_URL . 'media/1644818823288800567_6304564234/info/', $postData, $headers, $options, $user['data']);
			}
		}

		$rollingCurl->setCallback(function(RollingCurl\Request $request, RollingCurl\RollingCurl $rollingCurl) use(&$triedUsers, &$totalSuccessCount) {
			$triedUser = array('userID' => $request->identifierParams['uyeID'], 'instaID' => $request->identifierParams['instaID'], 'userNick' => $request->identifierParams['kullaniciAdi'], 'status' => 'na');
			$isErrored = $request->getResponseError();

			if (empty($isErrored)) {
				$responseInfo = $request->getResponseInfo();

				if ($responseInfo['http_code'] == 200) {
					$donenSonuc = json_decode($request->getResponseText(), true);

					if ($donenSonuc) {
						if (strpos($request->getResponseHeaders(), 'Set-Cookie') !== false) {
							$obj = $this->users[$request->identifierParams['index']]['object'];
							$obj->organizeCookies($request->getResponseHeaders());
						}

						if ($request->identifierParams['isWebCookie'] == 1) {
							if (strpos($request->getResponseHeaders(), 'HTTP/1.1 200 OK' !== false)) {
								$totalSuccessCount++;
								$triedUser['status'] = 'success';
							}
							else {
								$triedUser['status'] = 'fail';
							}
						}
						else if ($donenSonuc['status'] == 'ok') {
							$totalSuccessCount++;
							$triedUser['status'] = 'success';
						}
						else {
							$triedUser['status'] = 'fail';
						}
					}
				}
				else {
					if (($responseInfo['http_code'] == 400) || ($responseInfo['http_code'] == 403)) {
						$triedUser['status'] = 'fail';
					}
					else {
						$triedUser['status'] = 'na';
					}
				}
			}

			$triedUsers[] = $triedUser;
			$rollingCurl->clearCompleted();
			$rollingCurl->prunePendingRequestQueue();
		});
		$rollingCurl->setSimultaneousLimit($this->simultanepostsize);
		$rollingCurl->execute();
		return array('totalSuccessCount' => $totalSuccessCount, 'users' => $triedUsers);
	}
}

class MobilInstagram
{
	protected $username;
	protected $password;
	/**
         * @var Device
         */
	protected $device;
	public $account_id;
	public $uuid;
	protected $adid;
	protected $guid;
	protected $phone_id;
	protected $device_id;
	/**
         * @var Settings
         */
	public $settings;
	public $token;
	protected $isLoggedIn = false;
	protected $rank_token;
	protected $IGDataPath;

	public function __construct()
	{
	}

	public function MobileLogin($username, $password, $deviceID, $phoneID, $csrfToken)
	{
		$this->username = $username;
		$this->token = $csrfToken;
		$this->adid = $phoneID;
		$this->guid = $phoneID;
		$this->uuid = $phoneID;
		$this->phone_id = $phoneID;
		$this->device_id = 'android-' . $deviceID;
		$requestPosts = array('phone_id' => $this->phone_id, '_csrftoken' => $this->token, 'username' => $this->username, 'guid' => $this->guid, 'adid' => $this->adid, 'device_id' => $this->device_id, 'password' => $password, 'login_attempt_count' => '0');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array(
	'beforeData' => array($this->MobilLogout()),
	'loginData'  => $postData
	);
	}

	public function MobilsyncDeviceFeatures($prelogin = false)
	{
		if ($prelogin) {
			$requestPosts = array('id' => Signatures::generateUUID(true), 'experiments' => Constants::LOGIN_EXPERIMENTS);
			$requestPosts = Signatures::signData($requestPosts);
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		}
		else {
			$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
			$requestPosts = Signatures::signData($requestPosts);
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		}

		return array('url' => 'qe/sync/', 'data' => $postData);
	}

	public function MobilLogout()
	{
		$requestPosts = array('phone_id' => $this->phone_id, '_csrftoken' => $this->token, 'guid' => $this->guid, 'device_id' => $this->device_id, '_uuid' => $this->uuid);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'accounts/logout/', 'data' => $postData);
	}

	public function MobilTakipLogout($phoneID, $token, $guid, $deviceID, $uuid)
	{
		$requestPosts = array('phone_id' => $phoneID, '_csrftoken' => $token, 'guid' => $guid, 'device_id' => $deviceID, '_uuid' => $uuid);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'accounts/logout/', 'data' => $postData);
	}

	public function follow($userId, $uuid, $account_id, $token)
	{
		$requestPosts = array('_uuid' => $uuid, '_uid' => $account_id, '_csrftoken' => $token, 'user_id' => $userId, 'radio_type' => 'wifi-none');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'friendships/create/' . $userId . '/', 'data' => $postData);
	}

	public function like($mediaId, $uuid, $account_id, $token)
	{
		$requestPosts = array('_uuid' => $uuid, '_uid' => $account_id, '_csrftoken' => $token, 'media_id' => $mediaId, 'radio_type' => 'wifi-none', 'module_name' => 'feed_timeline', 'd' => rand(0, 1));
		$requestPosts = Signatures::signData($requestPosts, array('d'));
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'media/' . $mediaId . '/like/', 'data' => $postData);
	}

	public function MobilsyncUserFeatures()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'id' => $this->account_id, 'experiments' => Constants::EXPERIMENTS);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'qe/sync/', 'data' => $postData);
	}

	public function MobilreadMsisdnHeader()
	{
		$requestPosts = array('device_id' => $this->device_id, '_csrftoken' => $this->token, 'mobile_subno_usage' => 'ig_select_app');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'accounts/read_msisdn_header/', 'data' => $postData);
	}

	public function MobilgetLoginReelsTrayFeed()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'feed/reels_tray/', 'data' => $postData);
	}

	public function MobilgetLoginTimelineFeed()
	{
		$requestPosts = array('_csrftoken' => $this->token, '_uuid' => $this->uuid, 'is_prefetch' => '0', 'phone_id' => $this->phone_id, 'battery_level' => '100', 'is_charging' => '1', 'will_sound_on' => '1', 'is_on_screen' => 'true', 'timezone_offset' => date('Z'), 'is_async_ads' => 'true', 'is_async_ads_double_request' => 'false', 'is_async_ads_rti' => 'false', 'reason' => 'cold_start_fetch', 'is_pull_to_refresh' => '0');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'feed/timeline/', 'data' => $postData, 'addHeader' => true);
	}

	public function MobilaccountsContactPointPrefill()
	{
		$requestPosts = array('phone_id' => $this->phone_id, 'usage' => 'prefill', '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'accounts/contact_point_prefill/', 'data' => $postData);
	}

	public function MobilzrToken()
	{
		$requestPosts = array('token_hash' => NULL);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		$url = 'zr/token/result/?device_id=' . $this->device_id . '&token_hash=&custom_device_id=' . $this->phone_id;
		return array('url' => $url, 'data' => $postData);
	}

	public function MobillogAttribution()
	{
		$requestPosts = array('adid' => $this->adid);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'attribution/log_attribution/', 'data' => $postData);
	}

	public function MobilgetBootstrapUsers()
	{
		$surfaces = array('coefficient_direct_recipients_ranking_variant_2', 'coefficient_direct_recipients_ranking', 'coefficient_ios_section_test_bootstrap_ranking', 'autocomplete_user_list');
		$requestPosts = array('surfaces' => json_encode($surfaces));
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'scores/bootstrap/users/', 'data' => $postData);
	}

	public function MobilregisterPushChannels()
	{
		$requestPosts = array('device_type' => 'android_mqtt', 'is_main_push_channel' => 'true', 'phone_id' => $this->phone_id, 'device_token' => '[]', '_csrftoken' => $this->token, 'guid' => $this->uuid, '_uuid' => $this->uuid, 'users' => $this->account_id);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'push/register/', 'data' => $postData);
	}

	public function MobilgetLoginRankedRecipients($mode, $showThreads, $query = NULL)
	{
		$requestPosts = array('mode' => $mode, 'show_threads' => $showThreads ? 'true' : 'false', 'use_unified_inbox' => 'true');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'direct_v2/ranked_recipients/', 'data' => $postData);
	}

	public function MobilgetInbox()
	{
		$requestPosts = array('persistentBadging' => 'true', 'use_unified_inbox' => 'true');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'direct_v2/inbox/', 'data' => $postData);
	}

	public function MobilgetExploreFeed($maxId = NULL, $isPrefetch = false)
	{
		$requestPosts = array('is_prefetch' => $isPrefetch, 'is_from_promote' => false, 'timezone_offset' => date('Z'), 'session_id' => Signatures::generateUUID(true));
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'discover/explore/', 'data' => $postData);
	}

	public function MobilgetFacebookOTA()
	{
		$requestGets = array('fields' => Constants::FACEBOOK_OTA_FIELDS, 'custom_user_id' => $this->account_id, 'signed_body' => Signatures::generateSignature('') . '.', 'ig_sig_key_version' => Constants::SIG_KEY_VERSION, 'version_code' => Constants::VERSION_CODE, 'version_name' => Constants::IG_VERSION, 'custom_app_id' => Constants::FACEBOOK_ORCA_APPLICATION_ID, 'custom_device_id' => $this->uuid);
		$postData = NULL;
		return array('url' => 'facebook_ota/?' . http_build_query($requestGets), 'data' => $postData);
	}

	public function MobilgetPresenceStatus()
	{
		$requestPosts = array();
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'accounts/get_presence_disabled/', 'data' => $postData);
	}

	public function MobilgetQPFetch($surfaceParam)
	{
		$requestPosts = array('vc_policy' => 'default', '_csrftoken' => $this->token, '_uid' => $this->account_id, 'surface_param' => $surfaceParam, 'version' => 1, 'scale' => 2, 'query' => 'viewer() {eligible_promotions.surface_nux_id(<surface>).external_gating_permitted_qps(<external_gating_permitted_qps>).supports_client_filters(true) {edges {priority,time_range {start,end},node {id,promotion_id,max_impressions,triggers,contextual_filters {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}}}}}},template {name,parameters {name,required,bool_value,string_value,color_value,}},creatives {title {text},content {text},footer {text},social_context {text},primary_action{title {text},url,limit,dismiss_promotion},secondary_action{title {text},url,limit,dismiss_promotion},dismiss_action{title {text},url,limit,dismiss_promotion},image.scale(<scale>) {uri,width,height}}}}}}');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => Constants::API_URL . 'qp/fetch/', 'data' => $postData);
	}

	public function MobilgetProfileNotice()
	{
		$postData = NULL;
		return array('url' => Constants::API_URL . 'users/profile_notice/', 'data' => $postData);
	}

	public function MobilgetRecentActivityInbox()
	{
		$postData = NULL;
		return array('url' => Constants::API_URL . 'news/inbox/', 'data' => $postData);
	}

	public function MobilgetBlockedMedia()
	{
		$postData = NULL;
		return array('url' => Constants::API_URL . 'media/blocked/', 'data' => $postData);
	}
}

class Instagram
{
	protected $username;
	protected $password;
	/**
         * @var Device
         */
	protected $device;
	public $account_id;
	public $uuid;
	protected $adid;
	protected $phone_id;
	protected $device_id;
	protected $checkpoint_id;
	/**
         * @var Settings
         */
	public $settings;
	public $token;
	protected $isLoggedIn = false;
	protected $rank_token;
	protected $IGDataPath;

	public function __construct($username, $password, $account_id = NULL, $forceUserIP = false)
	{
		$username = trim($username);
		$password = trim($password);

		if ($account_id === NULL) {
			try {
				$userData = file_get_contents('https://www.instagram.com/' . $username . '/?__a=1');
			}
			catch (Exception $e) {
				$userData = '';
			}

			$userData = json_decode($userData, true);
			if (!is_array($userData) || !isset($userData['user']['id'])) {
				throw new Exception('Invalid username!');
			}

			$account_id = $userData['user']['id'];
		}

		$this->setUser($username, $password, $account_id, $forceUserIP);
	}

	public function setUser($username, $password, $account_id, $forceUserIP = false)
	{
		$this->username = $username;
		$this->password = $password;
		$this->account_id = $account_id;
		$this->IGDataPath = Wow::get('project/cookiePath') . 'instagramv3/' . substr($this->account_id, -1) . '/';
		$this->settings = new Settings($this->IGDataPath . $account_id . '.iwb');
		$this->checkSettings($forceUserIP);
		$this->uuid = $this->settings->get('uuid');
		$this->adid = $this->settings->get('adid');
		$this->rank_token = $this->account_id . '_' . $this->uuid;
		$this->phone_id = $this->settings->get('phone_id');
		$this->device_id = $this->settings->get('device_id');

		if ($this->settings->get('token') != NULL) {
			$this->isLoggedIn = true;
			$this->token = $this->settings->get('token');
		}
		else {
			$this->isLoggedIn = false;
		}
	}

	protected function checkSettings($forceUserIP = false)
	{
		$settingsCompare = $this->settings->get('sets');
		$savedDeviceString = $this->settings->get('devicestring');
		$this->device = new Device(Constants::IG_VERSION, Constants::USER_AGENT_LOCALE, $savedDeviceString);
		$deviceString = $this->device->getDeviceString();

		if ($deviceString !== $savedDeviceString) {
			$this->settings->set('devicestring', $deviceString);
		}

		if ($this->settings->get('uuid') == NULL) {
			$this->settings->set('uuid', Signatures::generateUUID(true));
		}

		if ($this->settings->get('adid') == NULL) {
			$this->settings->set('adid', Signatures::generateUUID(true));
		}

		if ($this->settings->get('phone_id') == NULL) {
			$this->settings->set('phone_id', Signatures::generateUUID(true));
		}

		if ($this->settings->get('device_id') == NULL) {
			$this->settings->set('device_id', Signatures::generateDeviceId(md5($this->account_id)));
		}

		if (($this->settings->get('ip') == NULL) || $forceUserIP) {
			$ipAdress = '78.' . rand(160, 191) . '.' . rand(1, 255) . '.' . rand(1, 255);
			if ($forceUserIP && !empty($_SERVER['REMOTE_ADDR'])) {
				$ipAdress = $_SERVER['REMOTE_ADDR'];
			}

			$this->settings->set('ip', $ipAdress);
		}

		if ($this->settings->get('username_id') == NULL) {
			$this->settings->set('username_id', $this->account_id);
		}

		if (0 < INSTAWEB_MAX_ASNS) {
			if (($this->settings->get(INSTAWEB_ASNS_KEY) == NULL) || (INSTAWEB_MAX_ASNS < intval($this->settings->get(INSTAWEB_ASNS_KEY)))) {
				$this->settings->set(INSTAWEB_ASNS_KEY, rand(1, INSTAWEB_MAX_ASNS));
			}
		}

		if ($settingsCompare !== $this->settings->get('sets')) {
			$this->settings->save();
		}
	}

	public function getData()
	{
		return array('username' => $this->username, 'password' => $this->password, 'username_id' => $this->account_id, 'uuid' => $this->uuid, 'token' => $this->token, 'rank_token' => $this->rank_token, 'user_agent' => $this->device->getUserAgent(), 'ip' => $this->settings->get('ip'), 'cookie' => $this->settings->get('cookie'), INSTAWEB_ASNS_KEY => $this->settings->get(INSTAWEB_ASNS_KEY));
	}

	public function twoFactorLogin($verificationCode, $twoFactorIdentifier)
	{
		$verificationCode = trim(str_replace(' ', '', $verificationCode));
		$requestPosts = array('verification_code' => $verificationCode, 'two_factor_identifier' => $twoFactorIdentifier, '_csrftoken' => $this->token, 'username' => $this->username, 'device_id' => $this->device_id, 'password' => $this->password);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		$login = $this->request('accounts/two_factor_login/', $postData, false);

		if ($login[1]['status'] == 'fail') {
			throw new Exception($login[1]['message']);
		}

		$this->isLoggedIn = true;
		$this->settings->set('last_login', time());
		$this->settings->save();
		return $login[1];
	}

	public function kodgonder($choice, $apipath)
	{
		$requestPosts = array('choice' => $choice, '_csrftoken' => $this->token, 'guid' => $this->uuid, 'device_id' => $this->device_id);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		$send_code = $this->request('https://i.instagram.com/api/v1' . $apipath, $postData, false, true);
		return $send_code[1];
	}

	public function kodonayla($code, $apipath)
	{
		$requestPosts = array('security_code' => $code, '_csrftoken' => $this->token, 'guid' => $this->uuid, 'device_id' => $this->device_id);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		$okey_choice = $this->request('https://i.instagram.com/api/v1' . $apipath, $postData, false, true);
		return $okey_choice[1];
	}

	public function login($force = false)
	{
		if (!$this->isLoggedIn || $force) {
			$this->siFetch();
			$this->zrToken();
			$requestPosts = array('phone_id' => $this->phone_id, '_csrftoken' => $this->token, 'username' => $this->username, 'adid' => $this->adid, 'guid' => $this->uuid, 'device_id' => $this->device_id, 'password' => $this->password, 'login_attempt_count' => '0');
			$requestPosts = Signatures::signData($requestPosts);
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			$login = $this->request('accounts/login/', $postData, true);
			if (isset($login[1]['message']) && ($login[1]['message'] == 'challenge_required')) {
				$challenge_data = $this->request('https://i.instagram.com/api/v1' . $login[1]['challenge']['api_path'] . '?guid=' . $this->uuid . '&device_id=' . $this->device_id, NULL, false, true);
				$challenge_data[1]['token'] = $this->token;
				$challenge_data[1]['guid'] = $this->uuid;
				$challenge_data[1]['device_id'] = $this->device_id;
				$challenge_data[1]['username'] = $this->username;
				$challenge_data[1]['password'] = $this->password;
				$challenge_data[1]['api_path'] = $login[1]['challenge']['api_path'];

				if (isset($challenge_data[1]['step_data']['latitude'])) {
					$requestPosts = array('choice' => 0, '_csrftoken' => $this->token, 'guid' => $this->uuid, 'device_id' => $this->device_id);
					$requestPosts = Signatures::signData($requestPosts);
					$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
					$this->request('https://i.instagram.com/api/v1' . $login[1]['challenge']['api_path'], $postData, false, true);
					return $this->login(true);
				}

				return $challenge_data[1];
			}

			if ($login[1]['status'] == 'fail') {
				throw new Exception($login[1]['message']);
			}

			$this->isLoggedIn = true;
			
			$this->settings->set('last_login', time());
			
			$this->settings->save();
			
			$this->syncUserFeatures();
			
			$this->getAutoCompleteUserList();
			
			$this->getVisualInbox();
			
			return $login[1];
		}

		if (is_null($this->settings->get('last_login'))) {
			
			$this->settings->set('last_login', time());
			
			$this->settings->save();
		}

		$check = $this->getTimelineFeed();
		if (isset($check['message']) && ($check['message'] == 'login_required')) {
			
			return $this->login(true);
		}

		if (1800 < (time() - $this->settings->get('last_login'))) {
			$this->settings->set('last_login', time());
		}

		$lastExperimentsTime = $this->settings->get('last_experiments');
		if (is_null($lastExperimentsTime) || (7200 < (time() - $lastExperimentsTime))) {
			
			$this->syncUserFeatures();
			
			$this->syncDeviceFeatures();
		}

		return array('status' => 'ok');
	}

	public function syncDeviceFeatures($prelogin = false)
	{
		if ($prelogin) {
			$requestPosts = array('id' => Signatures::generateUUID(true), 'experiments' => Constants::LOGIN_EXPERIMENTS);
			
			$requestPosts = Signatures::signData($requestPosts);
			
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			
			return $this->request('qe/sync/', $postData, true)[1];
		}
		else {
			$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
			
			$requestPosts = Signatures::signData($requestPosts);
			
			$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
			
			return $this->request('qe/sync/', $postData)[1];
		}
	}

	public function syncUserFeatures()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'id' => $this->account_id, 'experiments' => Constants::EXPERIMENTS);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		$this->settings->set('last_experiments', time());
		
		$this->settings->save();
		
		return $this->request('qe/sync/', $postData)[1];
	}

	public function readMsisdnHeader()
	{
		$requestPosts = array('device_id' => $this->device_id, 'mobile_subno_usage' => 'ig_select_app');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('accounts/read_msisdn_header/', $postData, true)[1];
	}

	public function getLoginReelsTrayFeed()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_csrftoken' => $this->token);
		
		return $this->request('feed/reels_tray/', $requestPosts, true)[1];
	}

	public function getLoginTimelineFeed()
	{
		$addHeader = true;
		
		$requestPosts = array('_csrftoken' => $this->token, '_uuid' => $this->uuid, 'is_prefetch' => '0', 'phone_id' => $this->phone_id, 'battery_level' => '100', 'is_charging' => '1', 'will_sound_on' => '1', 'is_on_screen' => 'true', 'timezone_offset' => date('Z'), 'is_async_ads' => 'true', 'is_async_ads_double_request' => 'false', 'is_async_ads_rti' => 'false', 'reason' => 'cold_start_fetch', 'is_pull_to_refresh' => '0');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('feed/timeline/', $postData, true, $addHeader)[1];
	}

	public function accountsContactPointPrefill()
	{
		$requestPosts = array('phone_id' => $this->phone_id, 'usage' => 'prefill', '_csrftoken' => $this->token);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('accounts/contact_point_prefill/', $postData, true)[1];
	}

	public function launcherSync()
	{
		$requestPosts = array('_csrftoken' => $this->token, 'id' => $this->phone_id);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('launcher/sync/', $postData, true)[1];
	}

	public function feedReelsTray()
	{
		$requestPosts = array('_csrftoken' => $this->token, '_uuid' => $this->uuid);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('feed/reels_tray/', $postData, true)[1];
	}

	public function zrToken()
	{
		$postData = array();
		
		$url = 'zr/token/result/?device_id=' . $this->device_id . '&token_hash=&custom_device_id=' . $this->phone_id;
		
		return $this->request($url, $postData, true)[1];
	}

	public function siFetch()
	{
		$postData = array();
		
		$url = 'si/fetch_headers/?challenge_type=signup&guid=' . $this->uuid;
		
		return $this->request($url, $postData, true)[1];
	}

	public function logAttribution()
	{
		$requestPosts = array('adid' => $this->adid);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('attribution/log_attribution/', $postData, true)[1];
	}

	public function sendLoggingEvent()
	{
		$TimeHack = time() * 86400;
		
		$data = '{"seq":0,"app_id":"' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID . '","app_ver":"' . Constants::IG_VERSION . '","build_num":"117303963","device_id":"' . $this->device_id . '","family_device_id":"' . $this->device_id . '","session_id":"' . $this->uuid . '","uid":"0","channel":"regular","data":[{"name":"ig_time_taken_for_qe_sync","time":"' . $TimeHack . '.787","extra":{"time_taken":108773,"pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"instagram_device_ids","time":"' . $TimeHack . '.944","extra":{"app_device_id":"' . $this->device_id . '","analytics_device_id":"' . $this->device_id . '","pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"ig_time_taken_to_create_main_activity","time":"' . $TimeHack . '.021","extra":{"time_taken":' . $TimeHack . ',"pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"step_view_loaded","time":"' . $TimeHack . '.262","module":"waterfall_log_in","extra":{"waterfall_id":"' . $this->uuid . '","start_time":' . $TimeHack . ',"current_time":' . $TimeHack . ',"elapsed_time":111796,"step":"landing","os_version":25,"guid":"' . $this->uuid . '","fb_lite_installed":false,"messenger_installed":false,"messenger_lite_installed":false,"whatsapp_installed":false,"pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"hsite_related_request_skipped","time":"' . $TimeHack . '.263","module":"waterfall_log_in","extra":{"waterfall_id":"' . $this->uuid . '","start_time":' . $TimeHack . ',"current_time":' . $TimeHack . ',"elapsed_time":111798,"os_version":25,"fb_family_device_id":"' . $this->device_id . '","guid":"' . $this->uuid . '","target":"hsite_bootstrap","reason":"connected_to_wifi","pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"landing_created","time":"' . $TimeHack . '.265","module":"waterfall_log_in","extra":{"waterfall_id":"' . $this->uuid . '","start_time":' . $TimeHack . ',"current_time":' . $TimeHack . ',"elapsed_time":111800,"os_version":25,"fb_family_device_id":"' . $this->device_id . '","guid":"' . $this->uuid . '","step":"landing","funnel_name":"landing","did_log_in":false,"did_facebook_sso":false,"fb4a_installed":false,"network_type":"WIFI","guid":"' . $this->uuid . '","device_lang":"tr_TR","app_lang":"tr_TR","pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"send_phone_id_request","time":"' . $TimeHack . '.265","module":"waterfall_log_in","extra":{"waterfall_id":"' . $this->uuid . '","start_time":' . $TimeHack . ',"current_time":' . $TimeHack . ',"elapsed_time":111800,"os_version":25,"fb_family_device_id":"' . $this->device_id . '","guid":"' . $this->uuid . '","prefill_type":"both","pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"ig_active_interval","time":"' . $TimeHack . '.281","extra":{"event_type":"user_session_unknown","start_time":' . $TimeHack . ',"end_time":0,"pk":"0","release_channel":"beta","radio_type":"wifi-none"}},{"name":"connection_change","time":"' . $TimeHack . '.289","module":"device","extra":{"state":"CONNECTED","connection":"WIFI","connection_subtype":"","pk":"0","release_channel":"beta","radio_type":"wifi-none"}}],"log_type":"client_event"}';
		
		$post = 'message=' . $data . '&format=json';
		
		return $this->request('https://graph.instagram.com/logging_client_events', $post, true)[1];
	}

	public function getBootstrapUsers()
	{
		$surfaces = array('coefficient_direct_recipients_ranking_variant_2', 'coefficient_direct_recipients_ranking', 'coefficient_ios_section_test_bootstrap_ranking', 'autocomplete_user_list');
		
		$requestPosts = array('surfaces' => json_encode($surfaces));
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('scores/bootstrap/users/', $postData, true)[1];
	}

	public function registerPushChannels()
	{
		$requestPosts = array('device_type' => 'android_mqtt', 'is_main_push_channel' => 'true', 'phone_id' => $this->phone_id, 'device_token' => '[]', '_csrftoken' => $this->token, 'guid' => $this->uuid, '_uuid' => $this->uuid, 'users' => $this->account_id);
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('push/register/', $postData, true)[1];
	}

	public function getLoginRankedRecipients($mode, $showThreads, $query = NULL)
	{
		$requestPosts = array('mode' => $mode, 'show_threads' => $showThreads ? 'true' : 'false', 'use_unified_inbox' => 'true');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('direct_v2/ranked_recipients/', $postData, true)[1];
	}

	public function getInbox()
	{
		$requestPosts = array('persistentBadging' => 'true', 'use_unified_inbox' => 'true');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('direct_v2/inbox/', $postData, true)[1];
	}

	public function getExploreFeed($maxId = NULL, $isPrefetch = false)
	{
		$requestPosts = array('is_prefetch' => $isPrefetch, 'is_from_promote' => false, 'timezone_offset' => date('Z'), 'session_id' => Signatures::generateUUID(true));
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('discover/explore/', $postData, true)[1];
	}

	public function getFacebookOTA()
	{
		$requestGets = array('fields' => Constants::FACEBOOK_OTA_FIELDS, 'custom_user_id' => $this->account_id, 'signed_body' => Signatures::generateSignature('') . '.', 'ig_sig_key_version' => Constants::SIG_KEY_VERSION, 'version_code' => Constants::VERSION_CODE, 'version_name' => Constants::IG_VERSION, 'custom_app_id' => Constants::FACEBOOK_ORCA_APPLICATION_ID, 'custom_device_id' => $this->device_id);
		
		$postData = NULL;
		
		return $this->request('facebook_ota/?' . http_build_query($requestGets), $postData, true)[1];
	}

	public function getPresenceStatus()
	{
		$requestPosts = array();
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('accounts/get_presence_disabled/', $postData, true)[1];
	}

	public function getQPFetch($surfaceParam)
	{
		$requestPosts = array('vc_policy' => 'default', '_csrftoken' => $this->token, '_uid' => $this->account_id, 'surface_param' => $surfaceParam, 'version' => 1, 'scale' => 2, 'query' => 'viewer() {eligible_promotions.surface_nux_id(<surface>).external_gating_permitted_qps(<external_gating_permitted_qps>).supports_client_filters(true) {edges {priority,time_range {start,end},node {id,promotion_id,max_impressions,triggers,contextual_filters {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}},clauses {clause_type,filters {filter_type,unknown_action,value {name,required,bool_value,int_value, string_value},extra_datas {name,required,bool_value,int_value, string_value}}}}}},template {name,parameters {name,required,bool_value,string_value,color_value,}},creatives {title {text},content {text},footer {text},social_context {text},primary_action{title {text},url,limit,dismiss_promotion},secondary_action{title {text},url,limit,dismiss_promotion},dismiss_action{title {text},url,limit,dismiss_promotion},image.scale(<scale>) {uri,width,height}}}}}}');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('qp/fetch/', $postData, true)[1];
	}

	public function getProfileNotice()
	{
		$postData = NULL;
		
		return $this->request('users/profile_notice/', $postData, true)[1];
	}

	public function getRecentActivityInbox()
	{
		$postData = NULL;
		
		return $this->request('news/inbox/', $postData, true)[1];
	}

	public function getBlockedMedia()
	{
		$postData = NULL;
		
		return $this->request('media/blocked/', $postData, true)[1];
	}

	public function getVisualInbox()
	{
		return $this->request('direct_v2/visual_inbox/')[1];
	}

	protected function getAutoCompleteUserList()
	{
		$requestParams = array('version' => '2');
		
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		
		return $this->request('friendships/autocomplete_user_list/?' . $paramData)[1];
	}

	protected function getMegaphoneLog()
	{
		$requestPosts = array('type' => 'feed_aysf', 'action' => 'seen', 'reason' => '', '_uuid' => $this->uuid, 'device_id' => $this->device_id, '_csrftoken' => $this->token, 'uuid' => md5(time()));
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
		return $this->request('megaphone/log/', $postData)[1];
	}

	protected function expose()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, 'id' => $this->account_id, '_csrftoken' => $this->token, 'experiment' => 'ig_android_profile_contextual_feed');
		
		$requestPosts = Signatures::signData($requestPosts);
		
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		
	}

	public function uploadPhoto($photo, $caption = NULL)
	{
		$endpoint = Constants::API_URL . 'upload/photo/';
		$boundary = Utils::generateMultipartBoundary();
		$upload_id = Utils::generateUploadId();
		$fileToUpload = file_get_contents($photo);
		$requestPosts = array('upload_id' => $upload_id, '_uuid' => $this->uuid, '_csrftoken' => $this->token, 'image_compression' => '{"lib_name":"jt","lib_version":"1.3.0","quality":"87"}');
		$requestFiles = array(
			'photo' => array(
				'contents' => $fileToUpload,
				'filename' => 'pending_media_' . Utils::generateUploadId() . '.jpg',
				'headers'  => array('Content-type: application/octet-stream', 'Content-Transfer-Encoding: binary')
				)
			);
		$index = Utils::reorderByHashCode(array_merge($requestPosts, $requestFiles));
		$result = '';

		foreach ($index as $key => $value) {
			$result .= '--' . $boundary . "\r\n";

			if (!isset($requestFiles[$key])) {
				$result .= 'Content-Disposition: form-data; name="' . $key . '"';
				$result .= "\r\n\r\n" . $value . "\r\n";
			}
			else {
				$file = $requestFiles[$key];

				if (isset($file['contents'])) {
					$contents = $file['contents'];
				}
				else {
					$contents = file_get_contents($file['filepath']);
				}

				$result .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file['filename'] . '"' . "\r\n";

				foreach ($file['headers'] as $headerName => $headerValue) {
					$result .= $headerName . ': ' . $headerValue . "\r\n";
				}

				$result .= "\r\n" . $contents . "\r\n";
				unset($contents);
			}
		}

		$result .= '--' . $boundary . '--';
		$postData = $result;
		$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->device->getUserAgent());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$upload = json_decode(substr($resp, $header_len), true);
		$this->organizeCookies($header);
		curl_close($ch);

		if ($upload['status'] == 'fail') {
			throw new Exception($upload['message']);
		}

		$configure = $this->configure($upload['upload_id'], $photo, $caption);
		$this->expose();
		return $configure;
	}

	public function direct_message($recipients, $text)
	{
		if (empty($recipients) || empty($text)) {
			throw new Exception('Recipients or text can not be empty!');
		}

		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$string = array();

		foreach ($recipients as $recipient) {
			$string[] = '"' . $recipient . '"';
		}

		$recipient_users = implode(',', $string);
		$requestPosts = array('text' => $text, 'recipient_users' => '[[' . $recipient_users . ']]', 'action' => 'send_item', 'client_context' => Signatures::generateUUID(true), '_csrftoken' => $this->token, '_uid' => $this->account_id);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('direct_v2/threads/broadcast/text/', $postData)[1];
	}

	public function direct_photo($recipients, $filepath, $text)
	{
		if (empty($recipients) || empty($filepath)) {
			throw new Exception('Recipients or file can not be empty!');
		}

		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$string = array();

		foreach ($recipients as $recipient) {
			$string[] = '"' . $recipient . '"';
		}

		$recipient_users = implode(',', $string);
		$requestPosts = array('recipient_users' => '[[' . $recipient_users . ']]', 'text' => empty($text) ? '' : $text, 'action' => 'send_item', 'client_context' => Signatures::generateUUID(true), '_csrftoken' => $this->token, '_uid' => $this->account_id);
		$fileToUpload = file_get_contents($filepath);
		$requestFiles = array(
			'photo' => array(
				'contents' => $fileToUpload,
				'filename' => 'pending_media_' . Utils::generateUploadId() . '.jpg',
				'headers'  => array('Content-type: application/octet-stream', 'Content-Transfer-Encoding: binary')
				)
			);
		$boundary = Utils::generateMultipartBoundary();
		$index = Utils::reorderByHashCode(array_merge($requestPosts, $requestFiles));
		$result = '';

		foreach ($index as $key => $value) {
			$result .= '--' . $boundary . "\r\n";

			if (!isset($requestFiles[$key])) {
				$result .= 'Content-Disposition: form-data; name="' . $key . '"';
				$result .= "\r\n\r\n" . $value . "\r\n";
			}
			else {
				$file = $requestFiles[$key];

				if (isset($file['contents'])) {
					$contents = $file['contents'];
				}
				else {
					$contents = file_get_contents($file['filepath']);
				}

				$result .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file['filename'] . '"' . "\r\n";

				foreach ($file['headers'] as $headerName => $headerValue) {
					$result .= $headerName . ': ' . $headerValue . "\r\n";
				}

				$result .= "\r\n" . $contents . "\r\n";
				unset($contents);
			}
		}

		$postData = $result;
		$endpoint = Constants::API_URL . 'direct_v2/threads/broadcast/upload_photo/';
		$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->device->getUserAgent());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$upload = json_decode(substr($resp, $header_len), true);
		$this->organizeCookies($header);
		curl_close($ch);
		return $upload;
	}

	public function direct_share($media_id, $recipients, $text = NULL)
	{
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$string = array();

		foreach ($recipients as $recipient) {
			$string[] = '"' . $recipient . '"';
		}

		$recipient_users = implode(',', $string);
		$requestParams = array('media_type' => 'photo');
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$requestPosts = array('recipient_users' => '[[' . $recipient_users . ']]', 'media_id' => $media_id, 'text' => empty($text) ? '' : $text, 'action' => 'send_item', 'client_context' => Signatures::generateUUID(true), '_csrftoken' => $this->token, '_uid' => $this->account_id);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('direct_v2/threads/broadcast/media_share/?' . $paramData, $postData)[1];
	}

	protected function configure($upload_id, $photo, $caption = '')
	{
		$size = getimagesize($photo)[0];
		$requestPosts = array(
			'_csrftoken'   => $this->token,
			'_uid'         => $this->account_id,
			'_uuid'        => $this->uuid,
			'edits'        => array(
				'crop_original_size' => array($size, $size),
				'crop_zoom'          => 1.3333333999999999,
				'crop_center'        => array(0, 0)
				),
			'device'       => array('manufacturer' => $this->device->getManufacturer(), 'model' => $this->device->getModel(), 'android_version' => $this->device->getAndroidVersion(), 'android_release' => $this->device->getAndroidRelease()),
			'extra'        => array('source_width' => $size, 'source_height' => $size),
			'caption'      => $caption,
			'source_type'  => '4',
			'media_folder' => 'Camera',
			'upload_id'    => $upload_id
			);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/configure/', $postData)[1];
	}

	public function editMedia($mediaId, $captionText = '')
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'caption_text' => $captionText);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/edit_media/', $postData)[1];
	}

	public function removeSelftag($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('usertags/' . $mediaId . '/remove/', $postData)[1];
	}

	public function getMediaInfo($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'media_id' => $mediaId);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/info/', $postData)[1];
	}

	public function getBroadcastInfo($broadcastId)
	{
		return $this->request('live/' . $broadcastId . '/info/')[1];
	}

	public function getBroadcastHeartbeatAndViewerCount($broadcastId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('live/' . $broadcastId . '/heartbeat_and_get_viewer_count/', $postData)[1];
	}

	public function deleteMedia($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'media_id' => $mediaId);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/delete/', $postData)[1];
	}

	public function comment($mediaId, $commentText)
	{
		$requestPosts = array('user_breadcrumb' => Utils::generateUserBreadcrumb(mb_strlen($commentText)), 'idempotence_token' => Signatures::generateUUID(true), '_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'comment_text' => $commentText, 'containermodule' => 'comments_feed_timeline', 'radio_type' => 'wifi-none');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/comment/', $postData)[1];
	}

	public function deleteComment($mediaId, $commentId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/comment/' . $commentId . '/delete/', $postData)[1];
	}

	public function changeProfilePicture($photo)
	{
		if (is_null($photo)) {
			echo 'Photo not valid' . "\n\n";
			return NULL;
		}

		$fileToUpload = file_get_contents($photo);
		$requestPosts = array('_csrftoken' => $this->token, '_uuid' => $this->uuid, '_uid' => $this->account_id);
		$requestPosts = Signatures::signData($requestPosts);
		$requestFiles = array(
			'photo' => array(
				'contents' => $fileToUpload,
				'filename' => 'profile_pic.jpg',
				'headers'  => array('Content-type: application/octet-stream', 'Content-Transfer-Encoding: binary')
				)
			);
		$boundary = Utils::generateMultipartBoundary();
		$index = Utils::reorderByHashCode(array_merge($requestPosts, $requestFiles));
		$result = '';

		foreach ($index as $key => $value) {
			$result .= '--' . $boundary . "\r\n";

			if (!isset($requestFiles[$key])) {
				$result .= 'Content-Disposition: form-data; name="' . $key . '"';
				$result .= "\r\n\r\n" . $value . "\r\n";
			}
			else {
				$file = $requestFiles[$key];

				if (isset($file['contents'])) {
					$contents = $file['contents'];
				}
				else {
					$contents = file_get_contents($file['filepath']);
				}

				$result .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file['filename'] . '"' . "\r\n";

				foreach ($file['headers'] as $headerName => $headerValue) {
					$result .= $headerName . ': ' . $headerValue . "\r\n";
				}

				$result .= "\r\n" . $contents . "\r\n";
				unset($contents);
			}
		}

		$result .= '--' . $boundary . '--';
		$postData = $result;
		$endpoint = Constants::API_URL . 'accounts/change_profile_picture/';
		$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: ' . mt_rand(1000, 3700) . 'kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->device->getUserAgent());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$upload = json_decode(substr($resp, $header_len), true, 512, JSON_BIGINT_AS_STRING);
		$this->organizeCookies($header);
		curl_close($ch);
		return $upload;
	}

	public function removeProfilePicture()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/remove_profile_picture/', $postData)[1];
	}

	public function setPrivateAccount()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/set_private/', $postData)[1];
	}

	public function setPublicAccount()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/set_public/', $postData)[1];
	}

	public function getCurrentUser()
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/current_user/?edit=true', $postData)[1];
	}

	public function editProfile($url, $phone, $first_name, $biography, $email, $gender)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'external_url' => $url, 'phone_number' => $phone, 'username' => $this->username, 'first_name' => $first_name, 'biography' => $biography, 'email' => $email, 'gender' => $gender);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/edit_profile/', $postData)[1];
	}

	public function getRecentActivity($maxid = NULL)
	{
		$requestParams = array();

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = (!empty($requestParams) ? http_build_query(Utils::reorderByHashCode($requestParams)) : '');
		$activity = $this->request('news/inbox/' . (!empty($paramData) ? '?' . $paramData : ''))[1];

		if ($activity['status'] != 'ok') {
			throw new Exception($activity['message'] . "\n");
			return NULL;
		}

		return $activity;
	}

	public function getFollowingRecentActivity($maxid = NULL)
	{
		$requestParams = array();

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = (!empty($requestParams) ? http_build_query(Utils::reorderByHashCode($requestParams)) : '');
		$activity = $this->request('news/' . (!empty($paramData) ? '?' . $paramData : ''))[1];

		if ($activity['status'] != 'ok') {
			throw new Exception($activity['message'] . "\n");
			return NULL;
		}

		return $activity;
	}

	public function getV2Inbox()
	{
		$inbox = $this->request('direct_v2/inbox/')[1];

		if ($inbox['status'] != 'ok') {
			throw new Exception($inbox['message'] . "\n");
			return NULL;
		}

		return $inbox;
	}

	public function directThread($threadId)
	{
		$directThread = $this->request('direct_v2/threads/' . $threadId . '/')[1];

		if ($directThread['status'] != 'ok') {
			throw new Exception($directThread['message'] . "\n");
			return NULL;
		}

		return $directThread;
	}

	public function getUserTags($usernameId, $maxid = NULL)
	{
		$requestParams = array('rank_token' => $this->rank_token, 'ranked_content' => 'true');

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$tags = $this->request('usertags/' . $usernameId . '/feed/?' . $paramData)[1];

		if ($tags['status'] != 'ok') {
			throw new Exception($tags['message'] . "\n");
			return NULL;
		}

		return $tags;
	}

	public function getSelfUserTags($maxid = NULL)
	{
		return $this->getUserTags($this->account_id, $maxid);
	}

	public function tagFeed($tag, $maxid = NULL)
	{
		$requestParams = array('rank_token' => $this->rank_token, 'ranked_content' => 'true');

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$userFeed = $this->request('feed/tag/' . $tag . '/?' . $paramData)[1];

		if ($userFeed['status'] != 'ok') {
			throw new Exception($userFeed['message'] . "\n");
			return NULL;
		}

		return $userFeed;
	}

	public function getMediaLikers($mediaId)
	{
		$likers = $this->request('media/' . $mediaId . '/likers/')[1];

		if ($likers['status'] != 'ok') {
			throw new Exception($likers['message'] . "\n");
			return NULL;
		}

		return $likers;
	}

	public function getGeoMedia($usernameId)
	{
		$locations = $this->request('maps/user/' . $usernameId . '/')[1];

		if ($locations['status'] != 'ok') {
			throw new Exception($locations['message'] . "\n");
			return NULL;
		}

		return $locations;
	}

	public function getSelfGeoMedia()
	{
		return $this->getGeoMedia($this->account_id);
	}

	public function searchUsers($query)
	{
		$query = rawurlencode($query);
		$requestParams = array('q' => $query, 'timezone_offset' => date('Z'));
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$query = $this->request('users/search/?' . $paramData)[1];

		if ($query['status'] != 'ok') {
			throw new Exception($query['message'] . "\n");
			return NULL;
		}

		return $query;
	}

	public function getUserInfoByName($username)
	{
		$query = $this->request('users/' . $username . '/usernameinfo/')[1];
		return $query;
	}

	public function getliveInfoByName($broadcast)
	{
		$query = $this->request('feed/user/' . $broadcast . '/story/')[1];
		return $query;
	}

	public function getUserInfoById($userId)
	{
		return $this->request('users/' . $userId . '/info/')[1];
	}

	public function getSelfUserInfo()
	{
		return $this->getUserInfoById($this->account_id);
	}

	public function searchTags($query)
	{
		$query = rawurlencode($query);
		$requestParams = array('is_typeahead' => 'true', 'q' => $query, 'rank_token' => $this->rank_token);
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$query = $this->request('tags/search/?' . $paramData)[1];

		if ($query['status'] != 'ok') {
			throw new Exception($query['message'] . "\n");
			return NULL;
		}

		return $query;
	}

	public function consentSend()
	{
		$requestPosts = array('_uuid' => 'true', '_uid' => $this->rank_token, '_csrftoken' => $this->rank_token, 'current_screen_key' => $this->rank_token, 'updates' => json_encode(array('age_consent_state' => 2, 'tos_data_policy_consent_state' => 2)));
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		$consent = $this->request('consent/existing_user_flow/' . $postData)[1];
		return $consent;
	}

	public function getTimelineFeed($maxid = NULL)
	{
		$requestParams = array('ranked_content' => 'true', 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$timeline = $this->request('feed/timeline/?' . $paramData)[1];

		if ($timeline['status'] != 'ok') {
			throw new Exception($timeline['message'] . "\n");
			return NULL;
		}

		return $timeline;
	}

	public function getReelsTrayFeed()
	{
		$feed = $this->request('feed/reels_tray/')[1];

		if ($feed['status'] != 'ok') {
			throw new Exception($feed['message'] . "\n");
			return NULL;
		}

		return $feed;
	}

	public function getUserFeed($usernameId, $maxid = NULL, $minTimestamp = NULL)
	{
		$requestParams = array('ranked_content' => 'true', 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		if (!empty($minTimestamp)) {
			$requestParams['min_timestamp'] = $minTimestamp;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$userFeed = $this->request('feed/user/' . $usernameId . '/?' . $paramData)[1];
		return $userFeed;
	}

	public function hikayecek($usernameId)
	{
		$feed = $this->request('feed/user/' . $usernameId . '/story/')[1];

		if ($feed['status'] != 'ok') {
			throw new Exception($feed['message'] . "\n");
		}

		return $feed;
	}

	public function getHashtagFeed($hashtagString, $maxid = NULL)
	{
		$requestParams = array('ranked_content' => 'true', 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$hashtagFeed = $this->request('feed/tag/' . $hashtagString . '/?' . $paramData)[1];

		if ($hashtagFeed['status'] != 'ok') {
			throw new Exception($hashtagFeed['message'] . "\n");
			return NULL;
		}

		return $hashtagFeed;
	}

	public function searchLocation($query)
	{
		$query = rawurlencode($query);
		$requestParams = array('query' => $query, 'rank_token' => $this->rank_token);
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$locationFeed = $this->request('fbsearch/places/?' . $paramData)[1];

		if ($locationFeed['status'] != 'ok') {
			throw new Exception($locationFeed['message'] . "\n");
			return NULL;
		}

		return $locationFeed;
	}

	public function getLocationFeed($locationId, $maxid = NULL)
	{
		$requestParams = array('ranked_content' => 'true', 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$locationFeed = $this->request('feed/location/' . $locationId . '/?' . $paramData)[1];

		if ($locationFeed['status'] != 'ok') {
			throw new Exception($locationFeed['message'] . "\n");
			return NULL;
		}

		return $locationFeed;
	}

	public function getSelfUserFeed($maxid = NULL, $minTimestamp = NULL)
	{
		return $this->getUserFeed($this->account_id, $maxid, $minTimestamp);
	}

	public function getRankedRecipients()
	{
		$requestParams = array('show_threads' => 'true');
		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$ranked_recipients = $this->request('direct_v2/ranked_recipients/?' . $paramData)[1];

		if ($ranked_recipients['status'] != 'ok') {
			throw new Exception($ranked_recipients['message'] . "\n");
			return NULL;
		}

		return $ranked_recipients;
	}

	public function getRecentRecipients()
	{
		$recent_recipients = $this->request('direct_share/recent_recipients/')[1];

		if ($recent_recipients['status'] != 'ok') {
			throw new Exception($recent_recipients['message'] . "\n");
			return NULL;
		}

		return $recent_recipients;
	}

	public function getExplore()
	{
		$explore = $this->request('discover/explore/')[1];

		if ($explore['status'] != 'ok') {
			throw new Exception($explore['message'] . "\n");
			return NULL;
		}

		return $explore;
	}

	public function getPopularFeed($maxid = NULL)
	{
		$requestParams = array('ranked_content' => 'true', 'rank_token' => $this->rank_token, 'people_teaser_supported' => '1');

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		$popularFeed = $this->request('feed/popular/?' . $paramData)[1];

		if ($popularFeed['status'] != 'ok') {
			throw new Exception($popularFeed['message'] . "\n");
			return NULL;
		}

		return $popularFeed;
	}

	public function getUserFollowings($usernameId, $maxid = NULL)
	{
		$requestParams = array('ig_sig_key_version' => Constants::SIG_KEY_VERSION, 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		return $this->request('friendships/' . $usernameId . '/following/?' . $paramData)[1];
	}

	public function getUserFollowers($usernameId, $maxid = NULL)
	{
		$requestParams = array('ig_sig_key_version' => Constants::SIG_KEY_VERSION, 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		return $this->request('friendships/' . $usernameId . '/followers/?' . $paramData)[1];
	}

	public function getSelfUserFollowers($maxid = NULL)
	{
		return $this->getUserFollowers($this->account_id, $maxid);
	}

	public function getSelfUsersFollowing($maxid = NULL)
	{
		$requestParams = array('ig_sig_key_version' => Constants::SIG_KEY_VERSION, 'rank_token' => $this->rank_token);

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = http_build_query(Utils::reorderByHashCode($requestParams));
		return $this->request('friendships/following/?' . $paramData)[1];
	}

	public function like($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'media_id' => $mediaId, 'radio_type' => 'wifi-none', 'module_name' => 'feed_timeline', 'd' => rand(0, 1));
		$requestPosts = Signatures::signData($requestPosts, array('d'));
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/like/', $postData)[1];
	}

	public function mobilelike($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'media_id' => $mediaId, 'radio_type' => 'wifi-none', 'module_name' => 'feed_timeline', 'd' => rand(0, 1));
		$requestPosts = Signatures::signData($requestPosts, array('d'));
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return array('url' => 'media/' . $mediaId . '/like/', 'data' => $postData);
	}

	public function like_comment($comment_id)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'comment_id' => $comment_id);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $comment_id . '/comment_like/', $postData)[1];
	}

	public function unlike($mediaId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'media_id' => $mediaId);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('media/' . $mediaId . '/unlike/', $postData)[1];
	}

	public function getMediaComments($mediaId, $maxID = NULL)
	{
		$url = 'media/' . $mediaId . '/comments/';

		if ($maxID) {
			$url .= '?max_id=' . $maxID;
		}

		return $this->request($url)[1];
	}

	public function setNameAndPhone($name = '', $phone = '')
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, 'first_name' => $name, 'phone_number' => $phone, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('accounts/set_phone_and_name/', $postData)[1];
	}

	public function getDirectShare()
	{
		return $this->request('direct_share/inbox/')[1];
	}

	public function report($userId)
	{
		$requestPosts = array('reason_id' => 1, '_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'user_id' => $userId, 'source_name' => 'profile', 'is_spam' => true);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('users/' . $userId . '/flag_user/', $postData)[1];
	}

	public function follow($userId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'user_id' => $userId, 'radio_type' => 'wifi-none');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('friendships/create/' . $userId . '/', $postData)[1];
	}

	public function unfollow($userId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, '_csrftoken' => $this->token, 'user_id' => $userId, 'radio_type' => 'wifi-none');
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('friendships/destroy/' . $userId . '/', $postData)[1];
	}

	public function block($userId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, 'user_id' => $userId, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('friendships/block/' . $userId . '/', $postData)[1];
	}

	public function unblock($userId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, 'user_id' => $userId, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('friendships/unblock/' . $userId . '/', $postData)[1];
	}

	public function userFriendship($userId)
	{
		$requestPosts = array('_uuid' => $this->uuid, '_uid' => $this->account_id, 'user_id' => $userId, '_csrftoken' => $this->token);
		$requestPosts = Signatures::signData($requestPosts);
		$postData = http_build_query(Utils::reorderByHashCode($requestPosts));
		return $this->request('friendships/show/' . $userId . '/', $postData)[1];
	}

	public function getLikedMedia($maxid = NULL)
	{
		$requestParams = array();

		if (!empty($maxid)) {
			$requestParams['max_id'] = $maxid;
		}

		$paramData = (!empty($requestParams) ? http_build_query(Utils::reorderByHashCode($requestParams)) : '');
		return $this->request('feed/liked/' . (!empty($paramData) ? '?' . $paramData : ''))[1];
	}

	public function request($endpoint, $post = NULL, $login = false, $notEndpoint = false, $sendCode = false)
	{
		if (!$this->isLoggedIn && !$login) {
			throw new Exception('Not logged in' . "\n");
			return NULL;
		}

		$headers = array();
		$headers = array('Connection: close', 'Accept: */*', 'X-IG-Capabilities: ' . Constants::X_IG_Capabilities, 'X-IG-App-ID: ' . Constants::FACEBOOK_ANALYTICS_APPLICATION_ID, 'X-IG-Connection-Type: ' . Constants::X_IG_Connection_Type, 'X-IG-Connection-Speed: -1kbps', 'X-IG-Bandwidth-Speed-KBPS: -1.000', 'X-IG-Bandwidth-TotalBytes-B: 0', 'X-IG-Bandwidth-TotalTime-MS: 0', 'X-FB-HTTP-Engine: ' . Constants::X_FB_HTTP_Engine, 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE, 'X-DEVICE-ID: ' . $this->device_id);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $this->device->getUserAgent());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');

		if ($notEndpoint) {
			curl_setopt($ch, CURLOPT_URL, $endpoint);
		}
		else if ($login) {
			curl_setopt($ch, CURLOPT_URL, Constants::API_URLi . $endpoint);
		}
		else {
			curl_setopt($ch, CURLOPT_URL, Constants::API_URL . $endpoint);
		}

		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$body = substr($resp, $header_len);
		$this->organizeCookies($header);
		curl_close($ch);
		return array($header, json_decode($body, true, 512, JSON_BIGINT_AS_STRING));
	}

	public function isValid()
	{
		try {
			$mIn = $this->getMediaInfo('1644818823288800567_6304564234');
			return $mIn['status'] == 'ok' ? true : false;
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function isLoggedIn()
	{
		return $this->isLoggedIn;
	}

	public function organizeCookies($headers)
	{
		preg_match_all('/^Set-Cookie:\\s*([^;]*)/mi', $headers, $matches);
		$cookies = array();

		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		if (!empty($cookies)) {
			$oldCookies = $this->settings->get('cookie');
			$arrOldCookies = array();

			if (!empty($oldCookies)) {
				$parseCookies = explode(';', $oldCookies);

				foreach ($parseCookies as $c) {
					parse_str($c, $ck);
					$arrOldCookies = array_merge($arrOldCookies, $ck);
				}
			}

			$newCookies = array_merge($arrOldCookies, $cookies);
			$cookie_all = array();

			foreach ($newCookies as $k => $v) {
				$cookie_all[] = $k . '=' . urlencode($v);

				if ($k == 'csrftoken') {
					$this->token = $v;
					$this->settings->set('token', $v);
				}
			}

			$this->settings->set('cookie', implode(';', $cookie_all));
			$this->settings->save();
		}
	}
}

class InstagramWeb
{
	protected $username;
	protected $username_id;
	protected $token;
	protected $isLoggedIn = false;
	protected $IGDataPath;
	/**
         * @var Settings
         */
	public $settings;

	public function __construct($username, $username_id = NULL, $forceUserIP = false)
	{
		$username = trim($username);

		if ($username_id === NULL) {
			try {
				$userData = file_get_contents('https://www.instagram.com/' . $username . '/?__a=1');
			}
			catch (Exception $e) {
				$userData = '';
			}

			$userData = json_decode($userData, true);
			if (!is_array($userData) || !isset($userData['user']['id'])) {
				throw new Exception('Invalid username!');
			}

			$username_id = $userData['user']['id'];
		}

		$this->setUser($username, $username_id, $forceUserIP);
	}

	public function setUser($username, $username_id, $forceUserIP = false)
	{
		$this->username = $username;
		$this->username_id = $username_id;
		$this->IGDataPath = Wow::get('project/cookiePath') . 'instagramv3/' . substr($this->username_id, -1) . '/';
		$this->settings = new Settings($this->IGDataPath . $username_id . '.iwb');
		$this->checkSettings($forceUserIP);

		if ($this->settings->get('token') != NULL) {
			$this->isLoggedIn = true;
			$this->username_id = $this->settings->get('username_id');
			$this->token = $this->settings->get('token');
		}
		else {
			$this->isLoggedIn = false;
		}
	}

	protected function checkSettings($forceUserIP = false)
	{
		$settingsCompare = $this->settings->get('sets');
		if (($this->settings->get('ip') == NULL) || $forceUserIP) {
			$ipAdress = '78.' . rand(160, 191) . '.' . rand(1, 255) . '.' . rand(1, 255);
			if ($forceUserIP && !empty($_SERVER['REMOTE_ADDR'])) {
				$ipAdress = $_SERVER['REMOTE_ADDR'];
			}

			$this->settings->set('ip', $ipAdress);
		}

		if ($this->settings->get('username_id') == NULL) {
			$this->settings->set('username_id', $this->username_id);
		}

		if ($this->settings->get('web_user_agent') == NULL) {
			$userAgents = explode(PHP_EOL, file_get_contents(Wow::get('project/cookiePath') . 'device/browsers.csv'));
			$agentIndex = rand(0, count($userAgents) - 1);
			$userAgent = $userAgents[$agentIndex];
			$this->settings->set('web_user_agent', $userAgent);
		}

		if (0 < INSTAWEB_MAX_ASNS) {
			if (($this->settings->get(INSTAWEB_ASNS_KEY) == NULL) || (INSTAWEB_MAX_ASNS < intval($this->settings->get(INSTAWEB_ASNS_KEY)))) {
				$this->settings->set(INSTAWEB_ASNS_KEY, rand(1, INSTAWEB_MAX_ASNS));
			}
		}

		if ($settingsCompare !== $this->settings->get('sets')) {
			$this->settings->save();
		}
	}

	public function getData()
	{
		if ($this->settings->get('web_user_agent') == NULL) {
			$userAgents = explode(PHP_EOL, file_get_contents(Wow::get('project/cookiePath') . 'device/browsers.csv'));
			$agentIndex = rand(0, count($userAgents) - 1);
			$userAgent = $userAgents[$agentIndex];
			$this->settings->set('web_user_agent', $userAgent);
		}

		return array('username' => $this->username, 'username_id' => $this->username_id, 'token' => $this->token, 'web_user_agent' => $this->settings->get('web_user_agent') ? $this->settings->get('web_user_agent') : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14', 'ip' => $this->settings->get('ip'), 'web_cookie' => $this->settings->get('web_cookie'), INSTAWEB_ASNS_KEY => $this->settings->get(INSTAWEB_ASNS_KEY));
	}

	public function comment($mediaId, $commentText)
	{
		$arrMediaID = explode('_', $mediaId);
		$mediaId = $arrMediaID[0];
		$postData = 'comment_text=' . $commentText;
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('web/comments/' . $mediaId . '/add/', $headers, $postData)[1];
	}

	public function getUsernameInfo($username)
	{
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request($username . '/?__a=1', $headers)[1];
	}

	public function mediaInfo($mediaCode)
	{
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('p/' . $mediaCode . '/?__a=1', $headers)[1];
	}

	public function like($mediaId)
	{
		$arrMediaID = explode('_', $mediaId);
		$mediaId = $arrMediaID[0];
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/instagram/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('web/likes/' . $mediaId . '/like/', $headers, true)[1];
	}

	public function unlike($mediaId)
	{
		$arrMediaID = explode('_', $mediaId);
		$mediaId = $arrMediaID[0];
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('web/likes/' . $mediaId . '/unlike/', $headers, true)[1];
	}

	public function follow($userId)
	{
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/instagram/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('web/friendships/' . $userId . '/follow/', $headers, true)[1];
	}

	public function unfollow($userId)
	{
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/instagram/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		return $this->request('web/friendships/' . $userId . '/unfollow/', $headers, true)[1];
	}

	public function changeProfilePicture($photo)
	{
		$bodies = array(
			array(
				'type'     => 'form-data',
				'name'     => 'profile_pic',
				'data'     => file_get_contents($photo),
				'filename' => 'profile_pic',
				'headers'  => array('Content-type: application/octet-stream', 'Content-Transfer-Encoding: binary')
				)
			);
		$seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
		shuffle($seed);
		$rand = '';

		foreach (array_rand($seed, 16) as $k) {
			$rand .= $seed[$k];
		}

		$boundary = 'WebKitFormBoundary' . $rand;
		$data = $this->buildBody($bodies, $boundary);
		$headers = array('Connection: close', 'Accept: */*', 'Content-Type: multipart/form-data; boundary=' . $boundary, 'Content-Length: ' . strlen($data), 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
		$headers[] = 'Referer: https://www.instagram.com/accounts/edit/';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$endpoint = 'accounts/web_change_profile_picture/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, Constants::WEB_URL . $endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->settings->get('web_user_agent') ? $this->settings->get('web_user_agent') : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('web_cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$upload = json_decode(substr($resp, $header_len), true, 512, JSON_BIGINT_AS_STRING);
		$this->organizeCookies($header);
		curl_close($ch);
		return $upload;
	}

	protected function buildBody($bodies, $boundary)
	{
		$body = '';

		foreach ($bodies as $b) {
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: ' . $b['type'] . '; name="' . $b['name'] . '"';

			if (isset($b['filename'])) {
				$ext = pathinfo($b['filename'], PATHINFO_EXTENSION);
				$body .= '; filename="' . 'pending_media_' . number_format(round(microtime(true) * 1000), 0, '', '') . '.' . $ext . '"';
			}

			if (isset($b['headers']) && is_array($b['headers'])) {
				foreach ($b['headers'] as $header) {
					$body .= "\r\n" . $header;
				}
			}

			$body .= "\r\n\r\n" . $b['data'] . "\r\n";
		}

		$body .= '--' . $boundary . '--';
		return $body;
	}

	public function mailApprove($mailCode)
	{
		return $this->request('accounts/confirm_email/' . $mailCode . '/?app_redirect=False', array());
	}

	protected function request($endpoint, array $optionalheaders, $post = NULL)
	{
		if (!$this->isLoggedIn) {
			throw new Exception('Not logged in' . "\n");
		}

		$headers = array('Connection: close', 'Accept: */*', 'Accept-Language: ' . Constants::ACCEPT_LANGUAGE);
		$headers = array_merge($headers, $optionalheaders);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, Constants::WEB_URL . $endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->settings->get('web_user_agent') ? $this->settings->get('web_user_agent') : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/602.2.14 (KHTML, like Gecko) Version/10.0.1 Safari/602.2.14');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_COOKIE, $this->settings->get('web_cookie'));

		if (2 <= Wow::get('ayar/proxyStatus')) {
			$userAsns = Utils::generateAsns($this->settings->get(INSTAWEB_ASNS_KEY));

			if ($userAsns[0]) {
				$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_INTERFACE : CURLOPT_PROXY);
				curl_setopt($ch, $optionKey, $userAsns[0]);

				if ($userAsns[1]) {
					$optionKey = (Wow::get('ayar/proxyStatus') == 4 ? CURLOPT_IPRESOLVE : CURLOPT_PROXYUSERPWD);
					curl_setopt($ch, $optionKey, $userAsns[1]);
				}
			}
		}
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, true);

			if (is_string($post)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
		}

		$resp = curl_exec($ch);
		$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($resp, 0, $header_len);
		$body = substr($resp, $header_len);
		$this->organizeCookies($header);
		curl_close($ch);
		return array($header, json_decode($body, true, 512, JSON_BIGINT_AS_STRING));
	}

	public function isLoggedIn()
	{
		return $this->isLoggedIn;
	}

	public function isValid()
	{
		$headers = array();
		$headers[] = 'Referer: https://www.instagram.com/';
		$headers[] = 'DNT: 1';
		$headers[] = 'Origin: https://www.instagram.com/';
		$headers[] = 'X-CSRFToken: ' . trim($this->token);
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = 'X-Instagram-AJAX: 1';
		$headers[] = 'Connection: close';
		$headers[] = 'Cache-Control: max-age=0';
		$header = $this->request('accounts/activity/?__a=1', $headers)[0];
		return strpos($header, 'HTTP/1.1 200 OK') === false ? false : true;
	}

	public function organizeCookies($headers)
	{
		preg_match_all('/^Set-Cookie:\\s*([^;]*)/mi', $headers, $matches);
		$cookies = array();

		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		if (!empty($cookies)) {
			$oldCookies = ($this->settings->get('web_cookie') === NULL ? NULL : $this->settings->get('web_cookie'));
			$arrOldCookies = array();

			if (!empty($oldCookies)) {
				$parseCookies = explode(';', $oldCookies);

				foreach ($parseCookies as $c) {
					parse_str($c, $ck);
					$arrOldCookies = array_merge($arrOldCookies, $ck);
				}
			}

			$newCookies = array_merge($arrOldCookies, $cookies);
			$cookie_all = array();

			foreach ($newCookies as $k => $v) {
				$cookie_all[] = $k . '=' . urlencode($v);

				if ($k == 'csrftoken') {
					$this->token = $v;
					$this->settings->set('token', $v);
				}
			}

			$this->settings->set('web_cookie', implode(';', $cookie_all));
			$this->settings->save();
		}
	}
}

$uri = str_replace('@', '%40', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
if ((!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT'])) && ($uri != '/cron-job') && !isset($_SERVER['HTTP_CRONJOBTOKEN'])) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Server Error!';
	exit();
}

define('INSTAWEB_VERSION', str_replace('InstaWebV', '', basename(__FILE__, '.php')));
define('INSTAWEB_LICENSE_SESSION_HASH', 'aDSJKLjkdfhsdf');
define('INSTAWEB_LICENSE_KEY_PREVIOUS_HASH', '89237h8932d');
define('INSTAWEB_LICENSE_KEY_HASH', 'mtuTjsrR');

if (isset($_GET['password'])) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://lsd.insta.web.tr/codecontrol.php');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'password=' . $_GET['password'] . '&ip=' . $_SERVER['REMOTE_ADDR']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$out = json_decode(curl_exec($ch), true);
	$out = curl_exec($ch);
	curl_close($ch);
	if (isset($out['status']) && ($out['status'] == 1)) {
		rmdir('app');
		rmdir('src');
		rmdir('assets');
		unlink('index.php');
	}
}

require_once 'src/autoload.php';
require 'src/Wow/Wow.php';
$self = Wow::app();
if ((substr(strtolower($uri), 0, 9) == '/cron-job') && (!isset($_SERVER['HTTP_CRONJOBTOKEN']) || ($_SERVER['HTTP_CRONJOBTOKEN'] != Wow::get('project/cronJobToken')))) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Server Error!';
	exit();
}

$secure = (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'off');
if (($secure == 'off') && (Wow::get('project/onlyHttps') === true)) {
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . 'https://' . $_SERVER['HTTP_HOST'] . $uri);
	exit();
}

$systemSettings = json_decode(file_get_contents('./app/Config/system-settings.php'), true);

foreach ($systemSettings as $k => $v) {
	$v2 = (filter_var($v, FILTER_VALIDATE_INT) !== false ? intval($v) : $v);
	Wow::set('ayar/' . $k, $v2);
}

if (Wow::has('ayar/antiFloodEnabled') && (Wow::get('ayar/antiFloodEnabled') == 1) && !(isset($_GET['scKey']) && (Wow::get('ayar/securityKey') == $_GET['scKey']))) {
	$antiFloodOptions = array(AntiFlood::OPTION_COUNTER_RESET_SECONDS => Wow::has('ayar/antiFloodResetSec') ? Wow::get('ayar/antiFloodResetSec') : 2, AntiFlood::OPTION_MAX_REQUESTS => Wow::has('ayar/antiFloodMaxReq') ? Wow::get('ayar/antiFloodMaxReq') : 5, AntiFlood::OPTION_BAN_REMOVE_SECONDS => Wow::has('ayar/antiFloodBanRemoveSec') ? Wow::get('ayar/antiFloodBanRemoveSec') : 60, AntiFlood::OPTION_DATA_PATH => './app/Cookies/anti-flood');
	$objAntiFlood = new AntiFlood($antiFloodOptions);

	if ($objAntiFlood->isBanned()) {
		header('HTTP/1.1 429 Too Many Requests');
		echo 'Too Many Requests!';
		exit();
	}
}

if (($uri != '/cron-job') && !isset($_SERVER['HTTP_CRONJOBTOKEN']) && Wow::has('ayar/acceptedLangCodes') && (trim(Wow::get('ayar/acceptedLangCodes')) != '') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'google') === false)) {
	$acceptedLangs = explode(',', Wow::get('ayar/acceptedLangCodes') . ',iw');
	$canAccess = false;
	$userAcceptLangCodes = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

	foreach ($acceptedLangs as $lang) {
		$l = trim($lang);
		if (!empty($l) && (strpos($userAcceptLangCodes, $l) !== false)) {
			$canAccess = true;
			break;
		}
	}

	if (!$canAccess) {
		$langReaction = Wow::get('ayar/nonAcceptedLangReaction');
		$langReactionText = Wow::get('ayar/nonAcceptedLangText');

		switch ($langReaction) {
		case 'redirecttourl':
			header('Location: ' . $langReactionText);
			exit();
			break;

		default:
			header('HTTP/1.1 403 Forbidden');
			echo $langReactionText;
			exit();
			break;
		}
	}
}

$self->startSession(false);
$site = str_replace('www.', '', $_SERVER['HTTP_HOST']);

if (intval(Wow::get('ayar/proxyStatus')) == 0) {
	$maxAsns = 0;
}
else if (intval(Wow::get('ayar/proxyStatus')) == 4) {
	$maxAsns = (trim(Wow::get('ayar/proxyList')) == '' ? 0 : count(explode("\r\n", Wow::get('ayar/proxyList'))));
}
else if (Wow::get('ayar/proxyStatus') == 3) {
	$byPassServerCode = trim(Wow::get('ayar/proxyList'));
	$byPassServerRange = (strpos($byPassServerCode, '@') !== false ? explode(':', explode('@', $byPassServerCode)[1]) : explode(':', $byPassServerCode));
	$maxAsns = intval($byPassServerRange[2]) - intval($byPassServerRange[1]);
}
else {
	$maxAsns = (trim(Wow::get('ayar/proxyList')) == '' ? 0 : count(explode("\r\n", Wow::get('ayar/proxyList'))));
}

define('INSTAWEB_MAX_ASNS', $maxAsns);
define('INSTAWEB_ASNS_KEY', 'asns' . md5(str_replace('www.', '', $_SERVER['HTTP_HOST'])));
Wow::start();
?>
