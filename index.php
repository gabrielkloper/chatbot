<?php

require __DIR__ . '/vendor/autoload.php';

/**Payload de exemplo no final do codigo */

use core\Megaapi;
use core\Model;

/**
 * Recebo o json da api
 */
$data = file_get_contents('php://input');

/**
 * Transformo o json em array
 */
$array = json_decode($data, true);
debugArray($array);


$fromMe  = $array["key"]["fromMe"]; //Mensagem recebida ou enviada (true -> Enviada / false -> Recebida)
$type    = $array["messageType"]; //Tipo de mensagem recebida


/**
 * Validação se é uma mensagem recebida ou enviada
 */
if (!$fromMe) {


    if ($type != "message.ack") {

        /**Pego as informações do array - MSG Texto*/
        $instance       = $array["instance_key"]; //Instancia
        $phoneConect    = $array["jid"]; //Telefone conectado na api
        $chatid         = $array["key"]["remoteJid"]; //Contato cliente
        $idMessage      = $array["key"]["id"]; //ID da mensagem
        $participant    = $array["key"]["participant"] ?? ""; //Participante que enviou a mensagem no grupo
        $time           = $array["messageTimestamp"]; //Hora e data que foi enviada
        $name           = $array["pushName"]; //Nome do contato
        $status          = $array["status"] ?? ""; //Status da mensagem
        $message        = empty($array["message"]["conversation"]) ? $array["message"]["extendedTextMessage"]["text"] : $array["message"]["conversation"]; //Mensagem do chat
        $titleList      = empty($array["message"]["listResponseMessage"]["tile"]) ? "" : $array["message"]["listResponseMessage"]["tile"]; //Titulo
        $idList         = empty($array["message"]["listResponseMessage"]["singleSelectReply"]["selectedRowId"]) ? "" : $array["message"]["listResponseMessage"]["singleSelectReply"]["selectedRowId"];


        //Mensagem inicial
        if ($message == "Oi" || $message == "oi" || $message == "Olá" || $message == "olá" || $message == "Ola" || $message == "ola") {

            Megaapi::text($chatid, "Ola, confirma o agendamento da sua consulta com o *Dr. Marcos* para o dia 30/10/2020 as 10:00 ?\n\n*1* - Sim\n*2* - Não");
            exit;
        }

        if ($message == "1") {

            Megaapi::text($chatid, "Agendamento confirmado com sucesso!, um dia antes da sua consulta enviaremos uma mensagem de lembrete.😊❤️");
            exit;
        } else if ($message == "2") {

            Megaapi::text($chatid, "Agendamento cancelado com sucesso!");
            exit;
        } else {

            //Megaapi::text($chatid, "Desculpe, não entendi o que você quis dizer, por favor digite *uma das opções abaixo*:\n\n*1* - Sim, confirmar minha consulta\n*2* - Não, cancelar minha consulta");
            Megaapi::listMessage($chatid, "Desculpe, não entendi o que você quis dizer!");
            exit;
        }
    }
    exit;
} else {

    /**
     * Retornos dos status das mensagens enviadas
     */
    switch ($type) {

        case 'message.ack':
            /**Pego as informações do array - Status de vizualização da mensagem*/
            $instance      = $array["instance_key"]; //Instancia
            $chatid        = $array["key"]["remoteJid"]; //Contato cliente
            $idMessage     = $array["key"]["id"]; //ID da mensagem
            $status        = replaceAck($array["update"]["status"]);
            break;
    }
}


/**
 * Função para debugar array com print_r;
 *
 * @param [array] $array
 */
function debugArray($array)
{

    echo "<pre>";
    print_r($array);
}

/**
 * Função responsavel por fazer o tratamento do status das mensagens 
 *
 * @param [string] $status
 * @return string $status
 */
function replaceAck($status)
{

    /**
     * Fazemos o tratamento do status
     */
    switch ($status) {
        case '2':
            $status = "Enviada";
            return $status;
            break;

        case '3':
            $status = "Entregue";
            return $status;
            break;

        case '4':
            $status = "Vizualizada";
            return $status;
            break;

        case '5':
            $status = "Escutou o audio / vizualixou o video";
            return $status;
            break;
    }
}


/**Payload Exemplo */
/*
{
    "instance_key": "megastart-dddddddd",
    "jid": "556191666583@s.whatsapp.net",
    "isBusiness": false,
    "messageType": "conversation",
    "key": {
      "remoteJid": "556195562618@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB07E4D81B5C8D7B8439B"
    },
    "messageTimestamp": 1698527956,
    "pushName": "Megaapi",
    "broadcast": false,
    "message": {
      "conversation": "oque",
      "messageContextInfo": {
        "deviceListMetadata": {
          "senderKeyHash": "joPUN2PIbtP95w==",
          "senderTimestamp": "1698423881",
          "recipientKeyHash": "Tb7KRpV4lae9UA==",
          "recipientTimestamp": "1697318791"
        },
        "deviceListMetadataVersion": 2
      }
    },
    "verifiedBizName": "Megaapi"
  }
  */