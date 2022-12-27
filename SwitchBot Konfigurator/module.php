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
            $data = array('deviceID' => '', 'command' => 'getDevices');
            $devicelist = $this->SendData($data = json_encode($data));
            $devicelist = json_decode($devicelist, true);
            //print_r($devices);
            $Values =  array();
            $devices = array();
            if (isset($devicelist['body']['infraredRemoteList'])) $devices = $devicelist['body']['infraredRemoteList'];
            if (isset($devicelist['body']['deviceList'])) $devices = array_merge($devices,$devicelist['body']['deviceList']);
            $this->SendDebug("Devices", json_encode($devices), 0);
            $guid = "{074E9906-6BB5-E403-3987-2C7E11EAF46C}";
            $Instances = IPS_GetInstanceListByModuleID($guid);
            
            // Get all the instances that are connected to the configurators I/O
            $connectedInstanceIDs = [];
            foreach ($Instances as $instanceID) {
                if (IPS_GetInstance($instanceID)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                    // Add the instance ID to a list for the given address. Even though addresses should be unique, users could break things by manually editing the settings
                    $connectedInstanceIDs[IPS_GetProperty($instanceID, 'deviceID')][] = $instanceID;
                }
            }
            
            // Configurator
            
            foreach ($devices as $device) {
                $ID	= 0;
                if (isset($device['remoteType'])) $device['deviceType'] = $device['remoteType'];
                foreach ($Instances as $Instance) {
                    //$this->SendDebug("Created Instances", IPS_GetObject($Instance)['ObjectName'] , 0);
                    if (IPS_GetProperty($Instance, 'deviceID')== $device['deviceId']) {
                        $ID = $Instance;
                    }
                }
                $Values[] = [
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
                        'name'           => 'SwitchBot ' . $device['deviceType'] . ' (' . $device['deviceName'] . ')'
                    ]
                ];
            }
            foreach ($connectedInstanceIDs as $address => $instanceIDs) {
                foreach ($instanceIDs as $index => $instanceID) {
                    // The first entry for each found address was already added as valid value
                    if (($index === 0) && (!array_search($address,$devices))) {
                        $this->SendDebug("Index ", $index, 0);
                        $this->SendDebug("Address ", $address, 0);
                        continue;
                    }
                    // However, if an address is not a found address or an address has multiple instances, they are erroneous
                    $this->SendDebug("Unused Device", IPS_GetName($instanceID), 0);
                    $Values[] = [
                        'deviceID' => $address,
                        'name' => IPS_GetName($instanceID),
                        'instanceID' => $instanceID
                    ];
                }
            }
            $this->SendDebug("Config Form", json_encode($Values), 0);
            return json_encode($Values);
        }

        protected function SendData($Buffer)
        {
            $return = $this->SendDataToParent(json_encode([
                'DataID' => "{950EE1ED-3DEB-AF74-4728-3A179CDB7100}",
                'Buffer' => utf8_encode($Buffer),
            ]));
            $this->SendDebug("Received from Gateway", $return, 0);
            return $return;
        }
    }
