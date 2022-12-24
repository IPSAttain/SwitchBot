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

            switch ($this->ReadPropertyString('deviceType')) {
                case 'Bot':
                case 'Plug':
                    $this->RegisterVariableBoolean('setState', $this->Translate('Press'), '~Switch', 20);
                    $this->EnableAction('setState');
                    $this->RegisterProfile('SwitchBot.toggle', 'TurnLeft', '', '', 0, 0, 0, '', 1);
                    IPS_SetVariableProfileAssociation('SwitchBot.toggle', 0, $this->Translate('Toggle'), 'TurnLeft', 0xFFFFFF);
                    $this->RegisterVariableInteger('toggle', $this->Translate('Toggle'), 'SwitchBot.toggle', 32);
                    $this->EnableAction('toggle');
                    break;
                    
                case 'Lock':
                    $this->RegisterVariableBoolean('setLock', $this->Translate('Lock'), '~Lock', 20);
                    $this->EnableAction('setLock');
                    break;
                
                case 'Curtain':
                    $this->RegisterVariableBoolean('setState', $this->Translate('Curtain'), '~ShutterMove', 20);
                    $this->EnableAction('setState');
                    $this->RegisterVariableInteger('setShutterPosition', $this->Translate('Curtain'),'~ShutterPosition.100', 21);
                    $this->EnableAction('setShutterPosition');
                    break;

                case 'Light':
                    $this->RegisterVariableBoolean('setState', $this->Translate('Press'), '~Switch', 20);
                    $this->EnableAction('setState');

                    $this->RegisterProfile('SwitchBot.setBrightnessUp', 'HollowArrowUp', '', '', 0, 0, 0, '' , 1);
                    IPS_SetVariableProfileAssociation('SwitchBot.setBrightnessUp', 0, $this->Translate('Brightness Up'), 'HollowArrowUp', 0xFFFFFF); 
                    $this->RegisterVariableInteger('brightnessUp', $this->Translate('Brightness Up'), 'SwitchBot.setBrightnessUp', 31);
                    $this->EnableAction('brightnessUp');

                    $this->RegisterProfile('SwitchBot.setBrightnessDown', 'HollowArrowDown', '', '', 0, 0, 0, '', 1);
                    IPS_SetVariableProfileAssociation('SwitchBot.setBrightnessDown', 0, $this->Translate('Brightness Down'), 'HollowArrowDown', 0xFFFFFF);
                    $this->RegisterVariableInteger('brightnessDown', $this->Translate('Brightness Down'), 'SwitchBot.setBrightnessDown', 32);
                    $this->EnableAction('brightnessDown');
                    break;
                
                case 'Color Bulb':
                case 'Strip Light':
                    $this->RegisterVariableBoolean('setState', $this->Translate('Light'), '~Switch', 20);
                    $this->EnableAction('setState');
                    $this->RegisterVariableInteger('setBrightness', $this->Translate('Brightness'), '~Intensity.100', 31);
                    $this->EnableAction('setBrightness');
                    $this->RegisterVariableInteger('setColor', $this->Translate('Color'), '~HexColor', 32);
                    $this->EnableAction('setColor');
                    $this->RegisterVariableInteger('setColorTemperature', $this->Translate('Color Temperature'), '~TWColor', 33);
                    $this->EnableAction('setColorTemperature');
                    $this->RegisterProfile('SwitchBot.toggle', 'TurnLeft', '', '', 0, 0, 0, '', 1);
                    IPS_SetVariableProfileAssociation('SwitchBot.toggle', 0, $this->Translate('Toggle'), 'TurnLeft', 0xFFFFFF);
                    $this->RegisterVariableInteger('toggle', $this->Translate('Toggle'), 'SwitchBot.toggle', 32);
                    $this->EnableAction('toggle');

                    break;
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
            $data = array('deviceID' => $this->ReadPropertyString('deviceID'), 'parameter' => 'default', 'commandType' => 'command');
            switch ($Ident) {
                case 'setState':
                    $data['command'] = 'turnOff';
                    if ($Value) $data['command'] = 'turnOn';
                    break;

                case 'setColor':
                    $data['command'] = $Ident;
                    $data['parameter'] = strval($Value >> 16 & 255) . ':' . strval($Value >> 8 & 255) . ':' . $Value & 255;
                    break;

                case 'setBrightness':
                case 'setColorTemperature':
                    $data['command'] = $Ident;
                    $data['parameter'] = strval($Value);
                    break;
                
                case 'brightnessUp':
                case 'brightnessDown':
                case 'toggle':
                    $data['command'] = $Ident;
                    break;

                case 'setLock':
                    $data['command'] = 'unlock';
                    if ($Value) $data['command'] = 'lock';
                    break;
                
                case 'setShutterPosition':
                    $data['command'] = 'setPosition';
                    if ($this->ReadPropertyBoolean('deviceMode')) $data['parameter'] = '0,1,' . $Value;
                    else $data['parameter'] = '0,0,' . $Value;

                default:
                    $data['command'] = 'unknown';
            }
            $this->SendDebug(__FUNCTION__, $data['command'], 0);
            $return = json_decode($this->SendData($data = json_encode($data)), true); // Send Command to Splitter
            if ($return['message'] == 'success' && $Ident == 'setState') {
                $this->SetValue($Ident, $Value);
                if ($this->ReadPropertyBoolean('deviceMode')) {
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

