<?php

declare(strict_types=1);
	class SwitchBotDevice extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent('{652A1EF5-9461-A361-8D30-80A4DD532931}');
			$this->RegisterPropertyString('deviceID', "");
			$this->RegisterPropertyString('deviceName', "");
			$this->RegisterPropertyString('deviceType', "");
			$this->RegisterPropertyBoolean('deviceMode',false);

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

			switch ($this->ReadPropertyString('deviceType')) {
				case 'Bot':
					$this->RegisterVariableBoolean('setState', $this->Translate('Press'), '~Switch', 20);
					$this->EnableAction('setState');
					break;
			}
		}

		public function GetConfigurationForm() 
		{
			switch ($this->ReadPropertyString('deviceType'))
			{
				case 'Bot':
					$form = json_decode(file_get_contents(__DIR__ . '/../libs/formBotDevice.json'), true);
					break;

				default :
				$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			}
			return json_encode($form);
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
		}


		public function RequestAction($Ident,$Value)
		{
			switch ($Ident) {
				case 'setState':
					$pressMode = $this->ReadPropertyBoolean('deviceMode');
					$data = array();
					$data['deviceID'] = $this->ReadPropertyString('deviceID');
					if ($pressMode) 
					{
							$data['command'] = 'press';
					}
					else 
					{
						If ($this->GetValue($Ident)) 
						{
							$data['command'] = 'turnOff';
						}
						else 
						{
							$data['command'] = 'turnOn'; 
						}
					}
					$this->SendDebug(__FUNCTION__,$data['command'],0);
					$return = $this->Send_to_Parent($data = json_encode($data));
					$return = json_decode($return,true);
					$success = $return['message'];
					if ($success == 'success') {
						if (!$pressMode) {
							$return = $this->GetDeviceStatus;
							$return = json_decode($return,true);
							$state = $return['body']['power'];
							if ($state == 'on') $this->SetValue($Ident,true);
							else $this->SetValue($Ident,false);
						} else {
							$this->SetValue($Ident,true);
							IPS_Sleep(2000);
							$this->SetValue($Ident,false);
						}
					}
					break;
				
				default:
				$this->SetValue($Ident,$Value);
				}
			return $return;
		}

		public function GetDeviceStatus()
		{
			$data = array('deviceID' => $this->ReadPropertyString('deviceID'), 'command' => 'getStatus');
			$return = $this->Send_to_Parent($data = json_encode($data));
			$return = json_decode($return,true);
		}

		protected function Send_to_Parent($Buffer)
		{
			$return = $this->SendDataToParent(json_encode([
				'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
				'Buffer' => utf8_encode($Buffer),
			]));
			$this->SendDebug(__FUNCTION__,  $return , 0);
			return $return;
		}
	}