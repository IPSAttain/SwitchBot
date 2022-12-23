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
                        $this->RegisterVariableBoolean('setBrightness', $this->Translate('Brightness'), 'Brightness', 20);
                        $this->EnableAction('setBrightness');
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

                case 'setBrightness':
                    if ($Value) {
                        $data['command'] = 'brightnessUp';
                    } else {
                        $data['command'] = 'brightnessDown';
                    }
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
    }
