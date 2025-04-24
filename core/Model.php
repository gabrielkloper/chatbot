<?php

namespace core;
use \core\Database;

class Model
{

    private static $TIMEOUT_SECONDS = 300; // 5 minutos de timeout

    /**
     * Metodo para inserir dados
     *
     * @param [array] $array
     * @param [string] $table
     * @return array
     */
    public static function insert($array, $table)
    {

        $db = new Database($table);
        $id = $db->insert($array);

        /**Retorna um array de acordo com o ultimo id inserido */
        $user = $db->select('*', "WHERE id = '" . $id . "' ");
        $array = $user->fetch(\PDO::FETCH_ASSOC);

        return $array;
    }


    /**
     * Metodo para atualizar dados
     *
     * @param [array] $array
     * @param [string] $where
     * @param [string] $table
     * @return boolean
     */

    public static function update($array, $where, $table)
    {
        $db = new Database($table);
        $u = $db->update($array, $where);
        return $u;
    }


    public static function select($values, $where, $table)
    {

        $db = new Database($table);
        $a = $db->select($values, $where);
        $array = $a->fetchAll(\PDO::FETCH_ASSOC);

        if ($array) {
            return $array;
        } else {
            return false;
        }

    }


    /**
     * Metodo para deletar dados
     *
     * @param [type] $table
     * @param [type] $where
     * @return void
     */
    public static function delete($table, $where)
    {

        $db = new Database($table);
        $db->delete($where);
    }

    /**
     * Busca o estado atual de um chat
     *
     * @param string $chatId
     * @return array
     */
    public static function getChatState($chatId)
    {
        $db = new Database('chat_states');
        
        // Verificar timeout
        $result = $db->select('*, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_activity) as idle_time', "WHERE chat_id = :chat_id", [':chat_id' => $chatId]);
        $state = $result->fetch(\PDO::FETCH_ASSOC);
        
        if ($state) {
            // Se passou do tempo limite, resetar o estado
            if ($state['idle_time'] > self::$TIMEOUT_SECONDS) {
                self::resetChatState($chatId);
                return ['current_step' => 'timeout'];
            }
            
            return [
                'current_step' => $state['current_step'],
                'dados' => json_decode($state['dados'], true)
            ];
        }
        
        return ['current_step' => 'initial'];
    }

    /**
     * Cria ou atualiza o estado de um chat
     *
     * @param string $chatId
     * @param string $current_step
     * @param array $dados
     * @return boolean
     */
    public static function updateChatState($chatId, $current_step, $dados = [])
    {
        $db = new Database('chat_states');
        $currentState = self::getChatState($chatId);
        
        $dadosJson = json_encode($dados);
        $now = date('Y-m-d H:i:s');
        
        if (isset($currentState['current_step']) && $currentState['current_step'] !== 'initial') {
            return $db->update([
                'current_step' => $current_step,
                'dados' => $dadosJson,
                'updated_at' => $now,
                'last_activity' => $now
            ], "chat_id = :chat_id", [':chat_id' => $chatId]);
        } else {
            return $db->insert([
                'chat_id' => $chatId,
                'current_step' => $current_step,
                'dados' => $dadosJson,
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity' => $now
            ]);
        }
    }

    /**
     * Remove o estado de um chat
     *
     * @param string $chatId
     * @return boolean
     */
    public static function resetChatState($chatId)
    {
        $db = new Database('chat_states');
        return $db->delete("chat_id = :chat_id", [':chat_id' => $chatId]);
    }
}