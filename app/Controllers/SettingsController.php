<?php

namespace MS\Controllers;

use MS\Abstracts\Controller;
use MS\Services\GMailService;
use MS\Repositories\CampaignMessageRepository;

class SettingsController extends Controller {

	public function index() {

	}

	public function getPluginVersion(){
		$data = MAILSCOUT_VERSION;
		return $this->response($data);
	}
	public function getAllCampaignMessage(){
		$campaign_schedule = CampaignMessageRepository::Instance()->getAllCampaign();
		return $this->response($campaign_schedule);
	}
}
