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
			$Ident = 'SwitchBot';
			if ($this->ReadPropertyString('deviceType') == 'Bot') {
				$this->RegisterVariableBoolean($Ident . 'BotSwitch', $this->Translate('turnOn'), '~Switch', 20);
				$this->EnableAction($Ident.'BotSwitch');
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
			//$this->SendDebug("Elements", json_encode($Values), 0);
			return json_encode($form);
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
		}

		public function RequestAction($Ident,$Value)
		{
			$deviceMode = $this->ReadPropertyBoolean('deviceMode');
			if ($deviceMode) 
			{
				$command = 'press';
			}
			else 
			{
				If ($this->GetValue($Ident)) 
				{
					$command = 'turnOff';
				}
				else 
				{
					$command = 'turnOn'; 
				}
			}
			$this->SendDebug(__FUNCTION__,$command,0);
			$return = $this->Send_to_Parent($command . "\r" . $this->ReadPropertyString('deviceID'));
			$return = json_decode($return,true);
			$success = $return['message'];
			if ($success == 'success') {
				$this->SetValue($Ident,!$this->GetValue($Ident));
				if ($deviceMode) 
				{
					IPS_Sleep(2000);
					$this->SetValue($Ident,false);
				}
			}
			return $return;
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