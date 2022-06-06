<?php

declare(strict_types=1);
	class SwitchBotKonfigurator extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent('{652A1EF5-9461-A361-8D30-80A4DD532931}');

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
		}
		public function GetConfigurationForm() 
		{
			$Values = json_decode($this->GetFormData());	
			$this->SendDebug("Elements", json_encode($Values), 0);
			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			$form['actions'][0]['values'] = $Values;
			return json_encode($form);
		}
		
		private function GetFormData()
		{
			$devices = $this->SendData();
			$devices = json_decode($devices,true);
			$devices = $devices['body']['deviceList'];

			$guid = "{074E9906-6BB5-E403-3987-2C7E11EAF46C}";
			$Instances = IPS_GetInstanceListByModuleID($guid);
			
			// Configurator
			$Values = array();
			//$this->LogMessage(__FUNCTION__.print_r($devices,true) , 10206);
			foreach ($devices as $device)
			{
				$ID	= 0;
				foreach ($Instances as $Instance){
					//$this->SendDebug("Created Instances", IPS_GetObject($Instance)['ObjectName'] , 0);
					if (IPS_GetProperty($Instance,'deviceID')== $device['deviceId'])
					{
						$ID = $Instance;
					}
				}
				$Values[] = [
					'instanceID' => $ID,
					'deviceName' => $device['deviceName'],
					'deviceID'   => $device['deviceId'],
					'deviceType' => $device['deviceType'],
					'create'	 => 
					[
						"moduleID"       => $guid,
						"configuration"  => [
							"deviceID"   => $device['deviceId'],
							"deviceName" => $device['deviceName'],
							"deviceType" => $device['deviceType']
						],
						'name'           => 'SwitchBot ' . $device['deviceType'] . ' (' . $device['deviceName'] . ')'
					]
				];
			}
			return json_encode($Values);
		}

		protected function SendData() {
			$return = $this->SendDataToParent(json_encode([
				'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
				'Buffer' => utf8_encode("getDevices"),
			]));
			$this->SendDebug("Received from Gateway", $return , 0);
			//$this->LogMessage(__FUNCTION__, $return , 10206);
			return $return;
		}
	}