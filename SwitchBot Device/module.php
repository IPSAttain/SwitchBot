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
            $this->RegisterPropertyBoolean('deviceMode', true);
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

            $stateVariable = true;
            $this->RegisterProfile('SwitchBot.UpDown', 'Bulb', '', '', 0, 1, 0, '' , 1);
            IPS_SetVariableProfileAssociation('SwitchBot.UpDown', 0, '▲', '', -1); 
            IPS_SetVariableProfileAssociation('SwitchBot.UpDown', 1, '▼', '', -1); 
            $this->RegisterProfile('SwitchBot.toggle', 'TurnLeft', '', '', 0, 0, 0, '', 1);
            IPS_SetVariableProfileAssociation('SwitchBot.toggle', 0, $this->Translate('Toggle'), 'TurnLeft', -1);

            switch ($this->ReadPropertyString('deviceType')) {
                case 'Plug':
                    $this->RegisterVariableInteger('toggle', $this->Translate('Toggle'), 'SwitchBot.toggle', 32);
                    $this->EnableAction('toggle');
                    break;
                    
                case 'Lock':
                    $stateVariable = false;
                    $this->RegisterVariableBoolean('setLock', $this->Translate('Lock'), '~Lock', 20);
                    $this->EnableAction('setLock');
                    break;
                
                case 'Curtain':
                    $stateVariable = false;
                    $this->RegisterVariableBoolean('setCurtain', $this->Translate('Curtain'), '~ShutterMove', 20);
                    $this->EnableAction('setCurtain');
                    $this->RegisterVariableInteger('setPosition', $this->Translate('Curtain'),'~ShutterPosition.100', 21);
                    $this->EnableAction('setPosition');
                    break;

                case 'Color Bulb':
                case 'Strip Light':
                case 'Ceiling Light':
                case 'Ceiling Light Pro':
                    $this->RegisterVariableInteger('setBrightness', $this->Translate('Brightness'), '~Intensity.100', 31);
                    $this->EnableAction('setBrightness');
                    $this->RegisterVariableInteger('setColor', $this->Translate('Color'), '~HexColor', 32);
                    $this->EnableAction('setColor');
                    $this->RegisterVariableInteger('setColorTemperature', $this->Translate('Color Temperature'), '~TWColor', 33);
                    $this->EnableAction('setColorTemperature');
                    $this->RegisterVariableInteger('toggle', $this->Translate('Toggle'), 'SwitchBot.toggle', 32);
                    $this->EnableAction('toggle');
                    break;
                
                //  IR Devices
                case 'Light':
                    $this->RegisterVariableInteger('irBrightness', $this->Translate('Brightness'), 'SwitchBot.UpDown', 31);
                    $this->EnableAction('irBrightness');
                    break;

                case 'TV':
                case 'Set Top Box':
                    $this->RegisterProfile('SwitchBot.setChannel', 'TV', '', '', 0, 9, 1, '' , 1);
                    $this->RegisterVariableInteger('SetChannel', $this->Translate('Channel'), 'SwitchBot.setChannel', 25);
                    $this->EnableAction('SetChannel');
                    $this->RegisterVariableInteger('setMute', $this->Translate('Mute'), 'SwitchBot.toggle', 32);
                    $this->EnableAction('setMute');
                    $this->RegisterVariableInteger('irVolume', $this->Translate('Volume'), 'SwitchBot.UpDown', 30);
                    IPS_SetIcon($this->GetIDForIdent("irVolume"), 'TV');
                    $this->EnableAction('irVolume');
                    $this->RegisterVariableInteger('irChannel', $this->Translate('Channel'), 'SwitchBot.UpDown', 31);
                    IPS_SetIcon($this->GetIDForIdent("irChannel"), 'TV');
                    $this->EnableAction('irChannel');
                    break;

                case 'DVD':
                case 'Speaker':
                    $this->RegisterProfile('SwitchBot.setPlayback', 'TV', '', '', 0, 6, 1, '' , 1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 0, $this->Translate('FastForward'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 1, $this->Translate('Rewind'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 2, $this->Translate('Next'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 3, $this->Translate('Previous'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 4, $this->Translate('Pause'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 5, $this->Translate('Play'), '', -1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setPlayback', 6, $this->Translate('Stop'), '', -1);
                    $this->RegisterVariableInteger('setPlayback', $this->Translate('Playback'), 'SwitchBot.setPlayback', 35);
                    $this->EnableAction('setPlayback');
                    break;

                default:
            }
            if ($stateVariable) {
                // most devices support the "turnOn" / "turnOff" command
                $this->RegisterVariableBoolean('setState', $this->Translate('Press'), '~Switch', 10);
                $this->EnableAction('setState');
            }
        }

        public function GetConfigurationForm()
        {
            switch ($this->ReadPropertyString('deviceType')) {
                case 'Bot':
                    $form = json_decode(file_get_contents(__DIR__ . '/../libs/formBotDevice.json'), true);
                    break;
                case 'Light':
                    $form = json_decode(file_get_contents(__DIR__ . '/../libs/formLightIRDevice.json'), true);
                    break;
                case 'Curtain':
                    $form = json_decode(file_get_contents(__DIR__ . '/../libs/formCurtainDevice.json'), true);
                    break;
                default:
                    $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
            }
            return json_encode($form);
        }

        public function ReceiveData($JSONString)
        {
            $data = json_decode($JSONString);
            IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
        }


        public function RequestAction($Ident, $Value)
        {
            $data = array('deviceID' => $this->ReadPropertyString('deviceID'), 'parameter' => 'default', 'commandType' => 'command', 'command' => $Ident);
            switch ($Ident) {
                case 'setState':
                case 'setCurtain':
                    $data['command'] = ($Value ? 'turnOn' : 'turnOff');
                    break;

                case 'setColor':
                    $data['parameter'] = strval($Value >> 16 & 255) . ':' . strval($Value >> 8 & 255) . ':' . $Value & 255;
                    break;

                case 'setBrightness':
                case 'setColorTemperature':
                case 'SetChannel':
                    $data['parameter'] = strval($Value);
                    break;

                case 'irBrightness':
                    $data['command'] = ($Value ? 'brightnessDown' : 'brightnessUp');
                    break;

                case 'irVolume':
                    $data['command'] = ($Value ? 'volumeSub' : 'volumeAdd');
                    break;
            
                case 'irChannel':
                    $data['command'] = ($Value ? 'channelSub' : 'channelAdd');
                    break;
                
                case 'setLock':
                    $data['command'] = ($Value ? 'lock' : 'unlock');
                    break;
                
                case 'setPosition':
                    $data['parameter'] = ($this->ReadPropertyBoolean('deviceMode') ? '0,1,' . $Value : '0,0,' . $Value);

                case 'setPlayback':
                    $Playback = array('FastForward','Rewind','Next','Previous','Pause','Play','Stop');
                    $data['command'] = $Playback[$Value];

            }
            $this->SendDebug(__FUNCTION__, $data['command'], 0);
            $return = json_decode($this->SendData($data = json_encode($data)), true); // Send Command to Splitter
            if ($return['message'] == 'success') {
                $this->SetValue($Ident, $Value);
                if (!$this->ReadPropertyBoolean('deviceMode')&& $Ident == 'setState') {
                    IPS_Sleep(1000);
                    $this->SetValue($Ident, false);
                }
            }
            $this->SendDebug(__FUNCTION__, 'ReturnMessage: ' . $return['message'], 0);
            if (isset($return['body']['items'][0]['status']['battery'])) {
                $this->RegisterVariableInteger('battery', $this->Translate('Battery'), '~Battery.100', 30);
                $this->SetValue('battery',$return['body']['items'][0]['status']['battery']);
                $this->SendDebug(__FUNCTION__, 'ReturnBatteryValue: ' . $return['body']['items'][0]['status']['battery'], 0);
            }
            return $return;
        }

        public function DeviceStatus()
        {
            $data = array();
            $data['deviceID'] = $this->ReadPropertyString('deviceID');
            $data['command'] = 'getStatus';
            $this->SendDebug(__FUNCTION__, $data['command'], 0);
            $return = $this->SendData($data = json_encode($data));
            $return = json_decode($return, true);
            $this->SendDebug(__FUNCTION__, $return['message'], 0);
        }

        protected function SendData($Buffer)
        {
            $return = $this->SendDataToParent(json_encode([
                'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
                'Buffer' => utf8_encode($Buffer),
            ]));
            $this->SendDebug('Answer from API', $return, 0);
            return $return;
        }

        protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
        {
            if (!IPS_VariableProfileExists($Name)) {
                IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
            } else {
                $profile = IPS_GetVariableProfile($Name);
                if ($profile['ProfileType'] != $Vartype) {
                    $this->SendDebug(__FUNCTION__, 'Variable profile type does not match for profile ' . $Name, 0);
                }
            }
            IPS_SetVariableProfileIcon($Name, $Icon);
            IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
            if ($Digits != '') IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
            IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        }
    }
