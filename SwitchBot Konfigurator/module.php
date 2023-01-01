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
            $values = json_decode($this->GetFormData());
            $this->SendDebug("Elements", json_encode($values), 0);
            $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
            $form['actions'][0]['values'] = $values;
            return json_encode($form);
        }
        
        private function GetFormData()
        {
            $data = array('deviceID' => '', 'command' => 'getDevices');
            // all devices as json string
            $deviceJsonList = $this->SendData($data = json_encode($data));
            $deviceArray = json_decode($deviceJsonList, true);
            $values =  array();
            $devices = array();
            if (isset($deviceArray['body']['infraredRemoteList'])) $devices = $deviceArray['body']['infraredRemoteList'];
            if (isset($deviceArray['body']['deviceList'])) $devices = array_merge($devices,$deviceArray['body']['deviceList']);
            $guid = "{074E9906-6BB5-E403-3987-2C7E11EAF46C}";
            $instances = IPS_GetInstanceListByModuleID($guid);
            
            // Get all the instances that are connected to the configurators I/O
            $connectedInstanceIDs = [];
            foreach ($instances as $instanceID) {
                if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                    // Add the instance ID to a list for the given address. Even though addresses should be unique, users could break things by manually editing the settings
                    $connectedInstanceIDs[IPS_GetProperty($instanceID, 'deviceID')][] = $instanceID;
                }
            }
            
            // Configurator
            foreach ($devices as $device) {
                if (isset($device['remoteType'])) $device['deviceType'] = $device['remoteType'];
                foreach ($instances as $instance) {
                    // find out if instance already exist.
                    $ID = (IPS_GetProperty($instance, 'deviceID') == $device['deviceId'] ? $instance : 0);
                }
                $values[] = [
                    'instanceID' => $ID,
                    'deviceName' => $device['deviceName'],
                    'deviceID'   => $device['deviceId'],
                    'deviceType' => $device['deviceType'],
                    'hubDeviceId'=> $device['hubDeviceId'],
                    'create'	 =>
                    [
                        "moduleID"       => $guid,
                        "configuration"  => [
                            "deviceID"   => $device['deviceId'],
                            "deviceName" => $device['deviceName'],
                            "deviceType" => $device['deviceType']
                        ],
                        'name' => 'SwitchBot ' . $device['deviceType'] . ' (' . $device['deviceName'] . ')'
                    ]
                ];
            }
            foreach ($connectedInstanceIDs as $address => $instanceIDs) {
                foreach ($instanceIDs as $index => $instanceID) {
                    // The first entry for each found address was already added as valid value
                    if (($index === 0) && stripos($deviceJsonList,$address)) {
                        $this->SendDebug("Active Device", $address, 0);
                    } else {
                        // However, if an address is not a found address or an address has multiple instances, they are erroneous
                        $this->SendDebug("Erroneous Device", IPS_GetName($instanceID) . ' Type ' . IPS_GetProperty($instanceID, 'deviceType'), 0);
                        $values[] = [
                            'deviceID'    => $address,
                            'deviceName'  => IPS_GetName($instanceID),
                            'instanceID'  => $instanceID,
                            'hubDeviceId' => 'Not Connected',
                            'deviceType'  => IPS_GetProperty($instanceID, 'deviceType')
                        ];
                    }
                }
            }
            return json_encode($values);
        }

        protected function SendData($Buffer)
        {
            $return = $this->SendDataToParent(json_encode([
                'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
                'Buffer' => utf8_encode($Buffer),
            ]));
            $this->SendDebug("Received " . __FUNCTION__, $return, 0);
            return $return;
        }
    }
