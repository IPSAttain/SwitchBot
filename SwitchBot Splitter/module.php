<?php

declare(strict_types=1);
include_once __DIR__ . '/../libs/WebHookModule.php';
	class SwitchBotSplitter extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("Token", "");
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			if ($this->ReadPropertyString('Token')) {
				$cc_id = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
				if (IPS_GetInstance($cc_id)['InstanceStatus'] == IS_ACTIVE) {
					$webhook_url = CC_GetConnectURL($cc_id) . '/hook/switchbot/' . $this->InstanceID;
					$this->SendDebug(__FUNCTION__, "WebHook URL " . $webhook_url , 0);
					$return = $this->SetWebHook($webhook_url);
					$this->SendDebug(__FUNCTION__, "WebHook response " . $return , 0);
					$return = json_decode($return,true);
				}
				$this->SetStatus(IS_ACTIVE);
			} else {
				$this->SetStatus(IS_INACTIVE);
			}
		}

		public function ForwardData($JSONString)
		{
			
			$data = json_decode($JSONString,true);
			$data = json_decode($data['Buffer'],true);
			$this->SendDebug(__FUNCTION__, 'Command: ' . $data['command'] . '  deviceID: ' . $data['deviceID'], 0);
			$returndata = "";
			switch ($data['command'])
			{
				case 'turnOn':
				case 'turnOff':
				case 'press':
					$returndata = $this->PostToDevice($data['deviceID'],$data['command']);
					break;
				
				case 'getDevices':
					$returndata = $this->GetDevices();
					break;

				case 'getStatus':
					$returndata = $this->GetDeviceStatus($data['deviceID']);
					break;
				
				default:
					$returndata = $this->GetDevices();
					break;	
			}
			$this->SendDebug(__FUNCTION__, $returndata , 0);
			return $returndata;
		}

		protected function GetDevices()
		{
			$Token = $this->ReadPropertyString("Token");
			$url = "https://api.switch-bot.com/v1.0/devices";
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$headers = array(
			   "Accept: application/json",
			   "Authorization: Bearer " . $Token,
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			
			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$SwitchBotResponse = curl_exec($curl);
			curl_close($curl);
			return $SwitchBotResponse;
		}

		protected function PostToDevice($deviceID , $command)
		{
			$Token = $this->ReadPropertyString("Token");
			$url = "https://api.switch-bot.com/v1.0/devices/" . $deviceID . "/commands";
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$headers = array(
			   "Accept: application/json",
			   "Authorization: Bearer " . $Token,
			   "Content-Type: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			$data = array(
				'command' => $command,
				'parameter' => 'default',
				'commandType' => 'command'
			);
			$data = json_encode($data);

			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$SwitchBotResponse = curl_exec($curl);
			curl_close($curl);
			return $SwitchBotResponse;
		}

		protected function GetDeviceStatus($deviceID)
		{
			$Token = $this->ReadPropertyString("Token");
			$url = "https://api.switch-bot.com/v1.0/devices/" . $deviceID . "/status";
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$headers = array(
			   "Accept: application/json",
			   "Authorization: Bearer " . $Token,
			   "Content-Type: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$SwitchBotResponse = curl_exec($curl);
			curl_close($curl);
			return $SwitchBotResponse;
		}

		protected function SetWebHook($webHookurl)
		{
			$Token = $this->ReadPropertyString("Token");
			$url = "https://api.switch-bot.com/v1.0/webhook/setupWebhook";
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$headers = array(
			   "Accept: application/json",
			   "Authorization: Bearer " . $Token,
			   "Content-Type: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			$data = array(
				'action' => 'setupWebhook',
				'url' => $webHookurl,
				'deviceList' => 'ALL'
			);
			$data = json_encode($data);
			$this->SendDebug(__FUNCTION__, "API data " . $data , 0);

			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$SwitchBotResponse = curl_exec($curl);
			curl_close($curl);
			return $SwitchBotResponse;
		}
	}