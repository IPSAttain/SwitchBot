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
                    break;
                    
                case 'Light':
                        $this->RegisterVariableBoolean('setState', $this->Translate('Press'), '~Switch', 20);
                        $this->EnableAction('setState');
                        $this->RegisterProfile('SwitchBot.setBrightnessUp', 'HollowArrowUp', '', '', 0, 0, 0, '' , 1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setBrightnessUp', 0, $this->Translate('Brightness Up'), 'HollowArrowUp', 0xFFFFFF); 
                        // RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
                        $this->RegisterVariableInteger('setBrightnessUp', $this->Translate('Brightness Up'), 'SwitchBot.setBrightnessUp', 31);
                        $this->EnableAction('setBrightnessUp');
                        
                        $this->RegisterProfile('SwitchBot.setBrightnessDown', 'HollowArrowDown', '', '', 0, 0, 0, '', 1);
                        IPS_SetVariableProfileAssociation('SwitchBot.setBrightnessDown', 0, $this->Translate('Brightness Down'), 'HollowArrowDown', 0xFFFFFF);
                        $this->RegisterVariableInteger('setBrightnessDown', $this->Translate('Brightness Down'), 'SwitchBot.setBrightnessDown', 32);
                        $this->EnableAction('setBrightnessDown');
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
                    $switchMode = $this->ReadPropertyBoolean('deviceMode');
                    if (!$switchMode) {
                        $data['command'] = 'press';
                    } else {
                        if ($Value) {
                            $data['command'] = 'turnOn';
                        } else {
                            $data['command'] = 'turnOff';
                        }
                    }
                    $this->SendDebug(__FUNCTION__, $data['command'], 0);
                    $return = $this->SendData($data = json_encode($data));
                    $return = json_decode($return, true);
                    if (isset($return['body']['items'][0]['status']['battery'])) {
                        $this->RegisterVariableInteger('battery', $this->Translate('Battery'), '~Battery.100', 30);
                        $this->SetValue('battery',$return['body']['items'][0]['status']['battery']);
                        $this->SendDebug(__FUNCTION__, 'Battery: ' . $return['body']['items'][0]['status']['battery'], 0);
                    }
                    $success = $return['message'];
                    if ($success == 'success') {
                        $this->SetValue($Ident, $Value);
                        if (!$switchMode) {
                            IPS_Sleep(2000);
                            $this->SetValue($Ident, false);
                        }
                    }
                    break;

                case 'setBrightnessUp':
                    $data['command'] = 'brightnessUp';
                    $this->SendDebug(__FUNCTION__, $data['command'], 0);
                    $return = $this->SendData($data = json_encode($data));
                    $return = json_decode($return, true);
                    if ($return['message'] == 'success') $this->SetValue($Ident, $Value);
                    break;

                case 'setBrightnessDown':
                    $data['command'] = 'brightnessDown';
                    $this->SendDebug(__FUNCTION__, $data['command'], 0);
                    $return = $this->SendData($data = json_encode($data));
                    $return = json_decode($return, true);
                    if ($return['message'] == 'success') $this->SetValue($Ident, $Value);
                    break;

                default:
                    $this->SetValue($Ident, $Value);
                }
            $this->SendDebug(__FUNCTION__, 'ReturnMessage: ' . $return['message'], 0);
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

