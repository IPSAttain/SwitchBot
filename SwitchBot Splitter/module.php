<?php

declare(strict_types=1);
	class SwitchBotSplitter extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("Token", "");
			$this->RegisterTimer("Update", 300000, "SWB_UpdateData($this->InstanceID);");
			$this->RegisterPropertyInteger("Refresh",5);
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
			$this->SetTimerInterval("Update", $this->ReadPropertyInteger("Refresh")*60000);
		}

		public function UpdateData()
		{
			$this->SendDebug(__FUNCTION__, "Data Update requested " , 0);
			$SwitchBotData = $this->GetDevices();
			$this->SendDebug(__FUNCTION__, print_r($SwitchBotData,true) , 0);
			$this->SendDataToChildren(json_encode(Array("DataID" => "{96111B9D-5260-8CFD-A2C4-5393BFFA1EB5}", "Buffer" => $SwitchBotData)));
		}

		public function ForwardData($JSONString)
		{
			$data = json_decode($JSONString,true);
			//$this->LogMessage(__FUNCTION__. utf8_decode($data->Buffer) , 10206);
			$data = preg_split('/\n|\r\n?/', $data['Buffer']);
			//$this->LogMessage(__FUNCTION__. print_r($data) , 10206) ;
			$returndata = "";
			switch ($data[0])
			{
				case 'turnOn':
				case 'turnOff':
				case 'press':
					$returndata = $this->PostToDevice($data[1],$data[0]);
					break;
				
				case 'getDevices':
					$returndata = $this->GetDevices();
					break;
				
				default:
					$returndata = $this->GetDevices();
					break;	
			}
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
	}