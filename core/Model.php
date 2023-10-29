<?php

namespace core;
use \core\Database;

class Model
{

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


}