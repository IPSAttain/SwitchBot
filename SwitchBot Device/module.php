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
        $this->RegisterPropertyString('command', "turnOn");
        $this->RegisterPropertyString('parameter', "default");
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

        $this->SetReceiveDataFilter('.*' . $this->ReadPropertyString('deviceID') . '.*');
        $stateVariable = true;
        $this->RegisterProfile('SwitchBot.UpDown', 'Bulb', '', '', 0, 1, 0, '', 1);
        IPS_SetVariableProfileAssociation('SwitchBot.UpDown', 0, '▲', '', -1);
        IPS_SetVariableProfileAssociation('SwitchBot.UpDown', 1, '▼', '', -1);
        $this->RegisterProfile('SwitchBot.toggle', 'TurnLeft', '', '', 1, 1, 0, '', 1);
        IPS_SetVariableProfileAssociation('SwitchBot.toggle', 1, $this->Translate('Toggle'), 'TurnLeft', -1);

        switch ($this->ReadPropertyString('deviceType')) {
            case 'Bot':
                $this->RegisterVariableInteger('battery', $this->Translate('Battery'), '~Battery.100', 30);
                break;

            case 'Plug':
                $this->RegisterVariableInteger('toggle', $this->Translate('Toggle'), 'SwitchBot.toggle', 32);
                $this->EnableAction('toggle');
                break;

            case 'Lock':
            case 'Smart Lock Pro':
            case 'Smart Lock Ultra':
                $stateVariable = false;
                $this->RegisterVariableBoolean('lockState', $this->Translate('Lock'), '~Lock', 20);
                $this->EnableAction('lockState');
                break;

            case 'Curtain':
            case 'Curtain 3':
                $stateVariable = false;
                $this->RegisterVariableBoolean('setCurtain', $this->Translate('Curtain'), '~ShutterMove', 20);
                $this->EnableAction('setCurtain');
                $this->RegisterVariableInteger('setPosition', $this->Translate('Curtain'), '~ShutterPosition.100', 21);
                $this->EnableAction('setPosition');
                break;

            case 'Blind Tilt':
                $this->RegisterProfile('SwitchBot.blindTilt', 'Shutter', '', '', 0, 100, 25, '', 1);
                IPS_SetVariableProfileAssociation('SwitchBot.blindTilt', 0, $this->Translate('Up'), '', -1);
                IPS_SetVariableProfileAssociation('SwitchBot.blindTilt', 25, '25 %%', '', 0x00FF00);
                IPS_SetVariableProfileAssociation('SwitchBot.blindTilt', 50, $this->Translate('Open'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('SwitchBot.blindTilt', 75, '75 %%', '', 0x00FF00);
                IPS_SetVariableProfileAssociation('SwitchBot.blindTilt', 100, $this->Translate('Down'), '', -1);
                $stateVariable = false;
                $this->RegisterVariableInteger('setPositionBlind', $this->Translate('Blind'), 'SwitchBot.blindTilt', 21);
                $this->EnableAction('setPositionBlind');
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
            case 'DIY Light':
                $this->RegisterVariableInteger('irBrightness', $this->Translate('Brightness'), 'SwitchBot.UpDown', 31);
                $this->EnableAction('irBrightness');
                break;

            case 'TV':
            case 'Set Top Box':
                $this->RegisterProfile('SwitchBot.setChannel', 'TV', '', '', 0, 9, 1, '', 1);
                $this->RegisterVariableInteger('SetChannel', $this->Translate('Channel'), 'SwitchBot.setChannel', 25);
                $this->EnableAction('SetChannel');
                $this->RegisterVariableInteger('setMute', $this->Translate('Mute'), 'SwitchBot.toggle', 32);
                $this->EnableAction('setMute');
                $this->RegisterVariableInteger('irVolume', $this->Translate('Volume'), 'SwitchBot.UpDown', 30);
                $this->EnableAction('irVolume');
                $this->RegisterVariableInteger('irChannel', $this->Translate('Channel'), 'SwitchBot.UpDown', 31);
                $this->EnableAction('irChannel');
                break;

            case 'DVD':
            case 'Speaker':
                $this->RegisterProfile('SwitchBot.setPlayback', 'TV', '', '', 0, 6, 1, '', 1);
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
            case 'Robot Vacuum Cleaner S1':
            case 'Robot Vacuum Cleaner S1 Plus':
                $this->RegisterProfile('SwitchBot.setSuctionLevel', 'Intensity', '', '', 0, 3, 1, '', 1);
                $stateVariable = false;
                $this->RegisterVariableInteger('setVacuuming', $this->Translate('Vacuum'), '~Playback', 10);
                $this->EnableAction('setVacuuming');
                $this->RegisterVariableInteger('setSuctionLevel', $this->Translate('Suction Level'), 'SwitchBot.setSuctionLevel', 11);
                $this->EnableAction('setSuctionLevel');
                break;

            case 'Motion Sensor':
            case 'Contact Sensor':
            case 'Meter':
            case 'Meter Plus':
            case 'WoIOSensor':
            case 'Indoor Cam':
            case 'Pan/Tilt Cam':
                $stateVariable = false;
                break;

            default:
        }
        // most devices support the "turnOn" / "turnOff" command
        if ($stateVariable) {
            if ($this->ReadPropertyString('deviceType') == 'Bot' && !$this->ReadPropertyBoolean('deviceMode')) {
                // Press Mode for Bot
                $this->MaintainVariable('setState', $this->Translate('Press'), 1, 'SwitchBot.toggle', 10, true);
            } else {
                // Switch Mode for all Devices
                $this->MaintainVariable('setState', $this->Translate('State'), 0, '~Switch', 10, true);
            }
            $this->EnableAction('setState');
        }
        //$this->DeviceStatus();
    }

    public function RequestAction($Ident, $value)
    {
        $data = array('deviceID' => $this->ReadPropertyString('deviceID'), 'parameter' => 'default', 'commandType' => 'command', 'command' => $Ident);
        switch ($Ident) {
            case 'setState':
            case 'setCurtain':
                $data['command'] = ($value ? 'turnOn' : 'turnOff');
                break;

            case 'setColor':
                $data['parameter'] = strval($value >> 16 & 255) . ':' . strval($value >> 8 & 255) . ':' . $value & 255;
                break;

            case 'setBrightness':
            case 'setColorTemperature':
            case 'SetChannel':
                $data['parameter'] = strval($value);
                break;

            case 'irBrightness':
                $data['command'] = ($value ? 'brightnessDown' : 'brightnessUp');
                break;

            case 'irVolume':
                $data['command'] = ($value ? 'volumeSub' : 'volumeAdd');
                break;

            case 'irChannel':
                $data['command'] = ($value ? 'channelSub' : 'channelAdd');
                break;

            case 'lockState':
                $data['command'] = ($value ? 'lock' : 'unlock');
                break;

            case 'setPosition':
                $data['parameter'] = ($this->ReadPropertyBoolean('deviceMode') ? '0,1,' . $value : '0,0,' . $value);
                break;

            case 'setPositionBlind':
                switch ($value) {
                    case ($value <= 0):
                        $data['command'] = 'closeUp';
                        break;

                    case ($value >= 100):
                        $data['command'] = 'closeDown';
                        break;

                    default:
                        $data['command'] = 'setPosition';
                        //Value must set to a multiple of 2
                        $data['parameter'] = 'up;' . (intval($value)) * 2;
                }
                break;

            case 'setPlayback':
                $Playback = array('FastForward','Rewind','Next','Previous','Pause','Play','Stop');
                $data['command'] = $Playback[$value];
                break;

            case 'setVacuuming':
                switch ($value) {
                    case 1:
                        $data['command'] = 'stop';
                        break;

                    case 2:
                        $data['command'] = 'start';
                        break;

                    default:
                        $data['command'] = 'dock';
                }
                break;

            case 'setSuctionLevel':
                $data['parameter'] = strval($value);
                $data['command'] = 'PowLevel';
                break;
        }
        $this->SendDebug(__FUNCTION__, $data['command'] . ' ' . $data['parameter'], 0);
        // Send Command to Splitter
        $return = json_decode($this->SendDataToSplitter($data = json_encode($data)), true);
        $this->SendDebug(__FUNCTION__, $return['message'], 0);
        // Set status var
        if ($return['message'] == 'success' || $return['message'] == 'success!') {
            $this->SetValue($Ident, $value);
            if (isset($return['body']['items'])) {
                $this->ProcessReturnData($return['body']['items'][0]['status']);
            }
        } else {
            $this->LogMessage('Response from Switchbot Cloud: ' . $return['message'], KL_WARNING);
        }
        return $return;
    }

    protected function ProcessReturnData($returnData)
    {
        $i = 100;
        foreach ($returnData as $key => $value) {
            // sometimes the value is in capital letters sometimes as lower case
            // unifi all to lower case
            if (is_string($value)) {
                $value = strtolower($value);
            }
            switch ($key) {
                case 'temperature':
                    $this->RegisterVariableFloat($key, $this->Translate('Temperature'), '~Temperature', 10);
                    $this->SetValue($key, $value);
                    break;
                case 'humidity':
                    $this->RegisterVariableInteger($key, $this->Translate('Humidity'), '~Humidity', 20);
                    $this->SetValue($key, $value);
                    break;
                case 'battery':
                    $this->RegisterVariableInteger($key, $this->Translate('Battery'), '~Battery.100', 30);
                    $this->SetValue($key, $value);
                    break;
                case 'lightLevel':
                    $this->RegisterVariableInteger($key, $this->Translate('Lightlevel'), '~UVIndex', 40);
                    $this->SetValue($key, $value);
                    break;
                case 'moveDetected':
                    $this->RegisterVariableBoolean('detectionState', $this->Translate('Motion'), '~Motion', 50);
                    $this->SetValue('detectionState', ($value == 'true'));
                    break;
                case 'detectionState':
                    $this->RegisterVariableBoolean($key, $this->Translate('Motion'), '~Motion', 50);
                    $this->SetValue($key, ($value == 'detected'));
                    break;
                case 'position':
                case 'slidePosition':
                    if ($this->ReadPropertyString('deviceType') == 'Blind Tilt') {
                        $this->SetValue('setPositionBlind', $value);
                    } else {
                        $this->SetValue('setPosition', $value);
                    }
                    break;
                case 'power':
                    $this->RegisterVariableBoolean($key, 'Power', '~Switch', 40);
                    $this->SetValue($key, ($value == 'on'));
                    break;
                case 'calibrate':
                case 'isCalibrate':
                    $this->RegisterVariableBoolean('isCalibrate', $this->Translate('Is Calibrate'), '~Switch', 50);
                    $this->SetValue('isCalibrate', ($value == 'true'));
                    break;
                case 'doorState':
                    $this->RegisterVariableBoolean($key, $this->Translate('Door'), '~Door', 60);
                    $this->SetValue($key, ($value == 'opened'));
                    break;
                case 'openState':
                    $this->RegisterVariableBoolean($key, $this->Translate('Door'), '~Door', 65);
                    $this->SetValue($key, ($value == 'open'));
                    break;
                case 'lockState':
                    $isLocked = in_array($value, ['locked', 'latchboltlocked']);
                    $this->SetValue($key, $isLocked);
                    break;
                case 'isStuck':
                    $this->RegisterVariableBoolean($key, $this->Translate('Is Stuck'), '~Switch', 60);
                    $this->SetValue($key, ($value == 'true'));
                    break;
                case 'timeOfSample':
                    $this->RegisterVariableInteger($key, $this->Translate('timeOfSample'), '~UnixTimestamp', 100);
                    $this->SetValue($key, intval($value / 1000));
                    break;

                case 'hubDeviceId':
                case 'deviceId':
                case 'deviceMac':
                case 'deviceType':
                    // not to save in Symcon variables
                    break;
                default:
                    if (is_string($value)) {
                        $this->RegisterVariableString($key, $key, '', $i);
                        $this->SetValue($key, $value);
                        $i += 10;
                    }
            }
        }
    }

    public function ReceiveData($JSONString)
    // all returns from WebHook
    // Cloud -> WebHook -> Splitter -> Device
    {
        $data = json_decode($JSONString);
        $receivedData = json_decode(utf8_decode($data->Buffer), true);
        $this->SendDebug(__FUNCTION__, utf8_decode($data->Buffer), 0);
        $this->ProcessReturnData($receivedData['context']);
    }

    public function DeviceStatus()
    // Retrieve information from the device
    {
        $data = array();
        $data['deviceID'] = $this->ReadPropertyString('deviceID');
        $data['command'] = 'getStatus';
        $this->SendDebug(__FUNCTION__, $data['command'], 0);
        $return = $this->SendDataToSplitter($data = json_encode($data));
        $return = json_decode($return, true);
        $this->SendDebug(__FUNCTION__, $return['message'], 0);
        $this->ProcessReturnData($return['body']);
    }

    public function SendCommand()
    // test center in the configuration form
    {
        $data = array();
        $data['deviceID']  = $this->ReadPropertyString('deviceID');
        $data['command']   = $this->ReadPropertyString('command');
        $data['parameter'] = $this->ReadPropertyString('parameter');
        $data['commandType'] = 'command';
        $this->SendDebug(__FUNCTION__, $data['command'] . ' ' . $data['parameter'], 0);
        // Send Command to Splitter
        $return = json_decode($this->SendDataToSplitter($data = json_encode($data)), true);
        $this->ProcessReturnData($return['body']['items'][0]['status']);
    }

    protected function SendDataToSplitter($Buffer)
    // forward to the Splitter
    {
        $return = $this->SendDataToParent(json_encode([
            'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
            'Buffer' => utf8_encode($Buffer),
        ]));
        $this->SendDebug(__FUNCTION__, $return, 0);
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
        if ($Digits != '') {
            IPS_SetVariableProfileDigits($Name, $Digits);
        } //  Nachkommastellen
        if ($Vartype != 0) {
            IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        } // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    public function GetConfigurationForm()
    {
        switch ($this->ReadPropertyString('deviceType')) {
            case 'Bot':
                $form = file_get_contents(__DIR__ . '/../libs/formBotDevice.json');
                break;
            case 'Light':
                $form = file_get_contents(__DIR__ . '/../libs/formLightIRDevice.json');
                break;
            case 'Curtain':
            case 'Curtain 3':
                $form = file_get_contents(__DIR__ . '/../libs/formCurtainDevice.json');
                break;
            default:
                $form = file_get_contents(__DIR__ . '/form.json');
        }
        //$this->SendDebug(__FUNCTION__ , json_encode(json_decode($form,true)), 0);
        return $form;
    }
}
