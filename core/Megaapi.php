<?php

namespace core;

use core\Config;
use Exception;

/**
 * Classe PHP para utilização da APi whatsapp com a Megaapi
 */
class Megaapi
{


    /**
     * Metodo para gerar o qrcode HTML
     *
     * @return html
     */
    static function qrcode()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/instance/qrcode/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }


    /**
     * Metodo para gerar o qrcode formato Base64
     *
     * @return array
     */
    static function qrcodeBase64()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/instance/qrcode_base64/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Metodo logout na api
     *
     * @return json
     */
    static function logout()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/instance/' . Config::INSTANCE . '/logout',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    /**
     * Metodo para verificar o status da sua instance 
     *
     * @return json
     */
    static function status()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/instance/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    /**
     * Metodo para obter os dados do webhook
     *
     * @return array
     */
    static function webhook()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/webhook/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Metodo para setar o link do seu webhook
     *
     * @param [string] $url
     * @param [string] $status
     * @return json
     */
    static function configWebhook($url, $status)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/webhook/' . Config::INSTANCE . '/configWebhook',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "messageData": {
                "webhookUrl": "' . $url . '",
                "webhookEnabled": ' . $status . '
            }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    /**
     * Metodo para enviar mensagem de texto
     *
     * @param [string] $contact
     * @param [string] $message
     * @return json
     */
    static function text($contact, $message)
    {

        try {
            $data = array(
                "messageData" => array(
                    "to" => $contact,
                    "text" => $message
                )
            );

            $data = json_encode($data);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/text',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . Config::TOKEN
                ),
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode == 400) {
                throw new Exception("Erro ao enviar mensagem: HTTP status $httpCode, resposta: Bad Request");
            } else if ($httpCode == 403) {
                throw new Exception("Erro ao enviar mensagem: HTTP status $httpCode, resposta: Number not registered on WhatsApp");
            }

            echo $response;
        } catch (Exception $err) {
            $errorMsg = "Erro ao enviar mensagem para $contact: " . $err->getMessage();
            throw new Exception($errorMsg);
        }
    }


    /**
     * Metodo para enviar arquivo via URL
     *
     * @param [string] $contact
     * @param [string] $file
     * @param [string] $filename
     * @param [string] $type
     * @param [string] $caption
     * @param [string] $mineType
     * @return json
     */
    static function mediaUrl($contact, $file, $filename, $type, $caption, $mineType)
    {

        $data = array(
            "messageData" => array(
                "to"        => $contact,
                "url"       => $file,
                "fileName"  => $filename,
                "type"      => $type,
                "caption"   => $caption,
                "mimeType"  => $mineType
            )
        );

        $data = json_encode($data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/mediaUrl',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }



    /**
     * Metodo para converter os arquivos recebidos no webhook para base64
     *
     * @param [string] $mediaKey
     * @param [string] $directPath
     * @param [string] $url
     * @param [string] $mimetype
     * @param [string] $messageType
     * @return json
     */
    static function downloadMediaMessage($mediaKey, $directPath, $url, $mimetype, $messageType)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/instance/downloadMediaMessage/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "messageKeys": {
            "mediaKey": "' . $mediaKey . '",
            "directPath": "' . $directPath . '",
            "url": "' . $url . '",
            "mimetype": "' . $mimetype . '",
            "messageType": "' . $messageType . '"
        }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    /**
     * Enviar uma localização para um usuário do WhatsApp
     *
     * @param [string] $contacts
     * @param [string] $address
     * @param [string] $caption
     * @param [number] $latitude
     * @param [number] $longitude
     * @return array
     */
    static function location($contact, $address, $caption, $latitude, $longitude)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/location',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "messageData": {
            "to": "' . $contact . '",
            "address": "' . $address . '",
            "caption": "' . $caption . '",
            "latitude": ' . $latitude . ',
            "longitude": ' . $longitude . '
        }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }



    /**
     * Método para enviar uma mensagem de modelo interativo com listMessage para um usuário do WhatsApp
     *
     * @param [string] $contact
     * @param [string] $buttonText
     * @param [string] $text
     * @param [string] $title1
     * @param [string] $description1
     * @param [string] $title2
     * @param [string] $title3
     * @param [string] $description2
     * @param [string] $rowID
     * @return array
     */
    static function listMessage($contact, $text)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/listMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "messageData": {
                "to": "' . $contact . '",
                "buttonText": "Selecione",
                "text": "' . $text . '",
                "title": "*AGENDAMENTO DE CONSULTA*",
                "description": "Selecione uma das opções para confirmar ou cancelar sua consulta",
                "sections": [
                    {
                        "title": "Agendamento consulta",
                        "rows": [
                            {
                                "title": "Sim",
                                "description": "Quero confirmar minha consulta",
                                "rowId": "1"
                            },
                            {
                                "title": "Não",
                                "description": "Quero cancelar minha consulta",
                                "rowId": "2"
                            }
                        ]
                    }
                ],
                "listType": 0
            }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }



    /**
     * Envie uma mensagem com contato para um usuário do WhatsApp
     *
     * @param [string] $contacts
     * @param [string] $fullname
     * @param [string] $displayName
     * @param [string] $organization
     * @param [string] $phoneNumber
     * @return void
     */
    static function contactMessage($contacts, $fullname, $displayName, $organization, $phoneNumber)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/contactMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "messageData": {
            "to": "' . $contacts . '",
            "vcard": {
            "fullName": "' . $fullname . '",
            "displayName": "' . $displayName . '",
            "organization": "' . $organization . '",
            "phoneNumber": "' . $phoneNumber . '"
            }
        }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Encaminhar mensagem para o usuário
     *
     * @param [string] $contacts
     * @param [object] $key
     * @param [object] $message
     * @return array
     */
    static function forwardMessage($contacts, $key, $message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/sendMessage/' . Config::INSTANCE . '/forwardMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "messageData": {
            "to": "' . $contacts . '",
            "key": {},
            "message": {}
        }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Responder uma mensagem enviada
     *
     * @param [string] $contacts
     * @param [string] $text
     * @param [object] $key
     * @param [object] $message
     * @return array
     */
    static function quoteMessage($contacts, $text, $key, $message)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => '{{url}}sendMessage/' . Config::INSTANCE . '/quoteMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
          "messageData": {
            "to": "' . $contacts . '",
            "text": "' . $text . '",
            "key": {},
            "message": {}
          }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }



    /**
     * Lista todos os grupos
     *
     * @return array
     */
    static function listGroup()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/list/' . Config::INSTANCE,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }


    /**
     * Todos os detalhes do grupo
     *
     * @param [string] $jid
     * @return array
     */
    static function getGroup($jid)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/' . Config::INSTANCE . '/group/?jid=' . $jid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Criar um novo grupo
     *
     * @param [string] $group_name
     * @param [array] $participants
     * @return array
     */
    static function create($group_name, $participants)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/' . Config::INSTANCE . '/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "group_data": {
            "group_name": "' . $group_name . '",
            "participants": [
                ' . $participants . '
            ]
        }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }


    /**
     * Adicionar participantes no grupo
     *
     * @param [string] $jdi
     * @param [array] $participants
     * @return array
     */
    static function addParticipants($jdi, $participants)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/' . Config::INSTANCE . '/addParticipants',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "group_data": {
                "jid": "' . $jdi . '",
                "participants": [
                    ' . $participants . '
                ]
            }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Remover participantes do grupo
     *
     * @param [string] $jdi
     * @param [array] $participants
     * @return array
     */
    static function removeParticipants($jdi, $participants)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/' . Config::INSTANCE . '/removeParticipants',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "group_data": {
                "jid": "' . $jdi . '",
                "participants": [
                    ' . $participants . '
                ]
            }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }


    /**
     * Deixar o grupo
     *
     * @param [string] $jdi
     * @return array
     */
    static function leaveGroup($jdi)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://' . Config::HOST . '/rest/group/' . Config::INSTANCE . '/leaveGroup?jid=' . $jdi,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . Config::TOKEN
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }
}
