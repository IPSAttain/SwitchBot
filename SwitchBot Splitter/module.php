<?php

declare(strict_types=1);
class SwitchBotSplitter extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Token', '');
        $this->RegisterPropertyString('Secret', '');
        $this->RegisterPropertyBoolean('directConnection', false);
        $this->RegisterPropertyString('IPAddress', '127.0.0.1');
        $this->RegisterPropertyString('Port', '3777');
        //We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
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

        if ($this->ReadPropertyString('Token') && $this->ReadPropertyString('Secret')) {
            $cc_id = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
            //Only call this in READY state. On startup the WebHook instance might not be available yet
            if (IPS_GetKernelRunlevel() == KR_READY) {

                $this->RegisterHook('/hook/switchbot/' . $this->InstanceID);
            }
            // check webhook configuration
            $data = array('action' => 'queryUrl');
            $endpoint = 'queryWebhook';
            $return = json_decode($this->ModifyWebHook($endpoint, $data), true);
            switch ($return['message']) {
                case 'Unauthorized':
                case 'unauthorized':
                    $this->SetStatus(201); // wrong credentials
                    break;
                
                case 'success':
                case '':
                    $currentWebHookURL = "";
                    if (isset($return['body']['urls'][0])) $currentWebHookURL = $return['body']['urls'][0];
                    if ($this->ReadPropertyBoolean('directConnection')) {
                        $webHookURL = utf8_encode($this->ReadPropertyString('IPAddress')) . '/hook/switchbot/' . $this->InstanceID;
                    } else {
                        $webHookURL = CC_GetConnectURL($cc_id) . '/hook/switchbot/' . $this->InstanceID;
                        if (IPS_GetInstance($cc_id)['InstanceStatus'] != IS_ACTIVE) {
                            $this->SendDebug(__FUNCTION__, 'Symcon Connect Service is not active', 0);
                            $this->SetStatus(203);
                            return;
                        }
                    }
                    if ($currentWebHookURL == $webHookURL) {
                        $this->SendDebug(__FUNCTION__, 'WebHook match the current setting.', 0);
                        $this->SetStatus(IS_ACTIVE);
                        // no further action
                        return;
                    }
                    // remove the old entry
                    $data = array('action' => 'deleteWebhook', 'url' => $currentWebHookURL);
                    $endpoint = 'deleteWebhook';
                    $return = json_decode($this->ModifyWebHook($endpoint, $data), true);
                    // update the webhook to the current setting
                    $this->SendDebug(__FUNCTION__, 'WebHook Url: ' . $webHookURL, 0);
                    $data = array('action' => 'setupWebhook','url' => $webHookURL,'deviceList' => 'ALL');
                    $endpoint = 'setupWebhook';
                    $return = json_decode($this->ModifyWebHook($endpoint, $data), true);
                    $this->SetStatus(IS_ACTIVE);
                    break;

                default:
                    $this->SetStatus(204); // unknown
                    $this->SendDebug(__FUNCTION__, 'Unknown Return Message: -> ' . $return['message'], 0);
                    break;

            } 
        } else {
            // no credentials
            $this->SetStatus(202);
            $this->SendDebug(__FUNCTION__, 'No Credetials set', 0);
        }

    }


    protected function ProcessHookData()
    {
        $receivedData = file_get_contents("php://input");
        $this->SendDebug(__FUNCTION__, $receivedData, 0);
        //$this->SendDebug('Get',print_r($_GET, true),0);
        $result = $this->SendDataToChildren(json_encode(array("DataID" => "{96111B9D-5260-8CFD-A2C4-5393BFFA1EB4}", "Buffer" => $receivedData)));

    }

    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString, true);
        $data = json_decode($data['Buffer'], true);
        $returndata = "";
        switch ($data['command']) {
            case 'getDevices':
                $returndata = $this->GetDevices();
                break;

            case 'getStatus':
                $returndata = $this->GetDeviceStatus($data['deviceID']);
                break;

            default:
                $returndata = $this->PostToDevice($data['deviceID'], $data['command'], $data['parameter'], $data['commandType']);
                break;
        }
        $this->SendDebug(__FUNCTION__, $returndata, 0);
        return $returndata;
    }

    protected function GetDevices()
    {
        $url = "https://api.switch-bot.com/v1.1/devices";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->GetHeaders());
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $SwitchBotResponse = curl_exec($curl);
        curl_close($curl);
        return $SwitchBotResponse;
    }

    protected function PostToDevice($deviceID, $command, $parameter, $commandType)
    {
        $this->SendDebug(__FUNCTION__ . ' ' . $deviceID, 'Command: ' . $command . ' Parameter: ' . $parameter . ' Command Type: ' . $commandType, 0);
        $url = "https://api.switch-bot.com/v1.1/devices/" . $deviceID . "/commands";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->GetHeaders());

        $data = array(
            'command' => $command,
            'parameter' => json_decode($parameter),
            'commandType' => $commandType
        );
        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__, $data, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $SwitchBotResponse = curl_exec($curl);
        curl_close($curl);
        return $SwitchBotResponse;
    }

    protected function GetDeviceStatus($deviceID)
    {
        $this->SendDebug(__FUNCTION__, $deviceID, 0);
        $url = "https://api.switch-bot.com/v1.1/devices/" . $deviceID . "/status";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->GetHeaders());
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $SwitchBotResponse = curl_exec($curl);
        curl_close($curl);
        return $SwitchBotResponse;
    }

    protected function ModifyWebHook($endpoint, $data)
    {
        $url = 'https://api.switch-bot.com/v1.1/webhook/' . $endpoint;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->GetHeaders());

        $data = json_encode($data);
        $this->SendDebug(__FUNCTION__ . " => " . $endpoint, "API data: " . $data, 0);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $SwitchBotResponse = curl_exec($curl);
        $this->SendDebug(__FUNCTION__ . " => " . $endpoint, "Return " . $SwitchBotResponse, 0);
        curl_close($curl);
        return $SwitchBotResponse;
    }

    protected function GetHeaders()
    {
        $token = $this->ReadPropertyString("Token");
        $secret = $this->ReadPropertyString("Secret");
        $nonce = $this->guidv4();
        $t = time() * 1000;
        $data = utf8_encode($token . $t . $nonce);
        $sign = hash_hmac('sha256', $data, $secret, true);
        $sign = strtoupper(base64_encode($sign));
        //$this->SendDebug(__FUNCTION__ , 'NONCE: '. $nonce . ' TIME: ' . $t . ' SIGN: ' . $sign, 0);

        $headers = array(
            "Content-Type:application/json",
            "Authorization:" . $token,
            "sign:" . $sign,
            "nonce:" . $nonce,
            "t:" . $t
        );
        return $headers;
    }
    protected function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {

        //Never delete this line!
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->RegisterHook('/hook/switchbot/' . $this->InstanceID);
        }
    }

    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

}
