<?php

namespace System\Orm;

use System\Helpers\Text;

/**
 * ERQuery Class
 *
 * Query building
 *
 * @author anaeria
 */


class ERQuery implements \IteratorAggregate, \ArrayAccess {

    // Query
    public $belongsToDetails        = null;
    protected $model                = null;
    protected $tableAlias           = null;
    protected $resultColonnes       = [];
    protected $joinWhereTables      = [];
    protected $joinWhereContraintes = [];
    protected $joinSources          = [];
    protected $joinFirstTable       = null;
    protected $countFrom            = 0;
    protected $whereConditions      = [];
    protected $whereBinds           = ['types' => [], 'values' => []];
    protected $joinBinds            = ['types' => [], 'values' => []];
    protected $groupBy              = [];
    protected $havingConditions     = [];
    protected $havingBinds          = ['types' => [], 'values' => []];
    protected $orderBy              = [];
    protected $limit                = null;
    protected $offset               = null;
    protected $fromExpr             = null;
    protected $distinct             = false;
    protected $rawQuery             = null;
    protected $queryExecuted        = false;
    protected $results              = [];
    protected $lightMode            = 0;
    protected $selectAliases        = [];
    protected $cached               = null;
    protected $sqlQuery             = null;
    protected $bindParam            = null;
    protected $first                = false;
    protected $last                 = false;
    protected $index                = null;
    protected $entity               = null;
    // NOTE: TEMPORAIRE
    protected $autoSelectChild      = true;

    // Scopes
    protected $scopesToExecute        = [];
    protected $unscoped               = false;


    /**
     * Construteur
     *
     * @param string model
     */

    public function __construct($model) {
        $this->model = $model;
    }


    ###########################################################################
    #
    #  IteratorAggregate, ArrayAccess
    #
    ###########################################################################


    /**
     * Retourne le résultat de la requête sous la forme d'un objet ArrayIterator
     *
     * @return object
     */

    public function getIterator() {
        $this->runQuery();
        return new \ArrayIterator($this->results);
    }


    // -------------------------------------------------------------------------

    /**
     * Définit un offset pour les résultats
     *
     * @param mixed offset
     * @param mixed value
     */

    public function offsetSet($offset, $value) {
        $this->runQuery();
        if (is_null($offset)) {
            $this->results[] = $value;
        }
        else {
            $this->results[$offset] = $value;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie l'existance d'un offset
     *
     * @param mixed offset
     * @return boolean
     */

    public function offsetExists($offset) {
        $this->runQuery();
        return isset($this->results[$offset]);
    }


    // -------------------------------------------------------------------------

    /**
     * Détruit un offset
     *
     * @param mixed offset
     */

    public function offsetUnset($offset) {
        unset($this->results[$offset]);
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère un offset
     *
     * @param mixed offset
     * @return array | NULL
     */

    public function offsetGet($offset) {
        $this->runQuery();
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
    }


    ############################################################################
    #
    #  Gestion du Cache
    #
    ############################################################################


    /**
     * Active la mise en cache
     *
     * @return object
     */

    public function cache() {
        $this->cached = true;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Desactive la mise en cache
     *
     * @return object
     */

    public function noCache() {
        $this->cached = false;
        return $this;
    }



    ############################################################################
    #
    #  LightModes
    #
    ############################################################################


    /**
     * Défini le résultat en lecture comme tableau associatif
     *
     * @return object
     */

    public function assocArray() {
        $this->lightMode = 2;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini le résultat en lecture comme le tableau des clés primaires
     *
     * @return object
     */

    public function ids() {
        $model           = $this->model;
        $this->lightMode = 1;
        $tableAlias      = $this->tableAlias ? $this->tableAlias : $model::table();

        foreach ($model::getIdentifier($model::READ_TO_ARRAY) as $col) {
            $this->select($tableAlias . '.' . $col);
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini le modèle de lecture du résultat
     *
     * @param string
     * @return object
     */

    public function entity($className) {
        $this->entity = $className;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit la requête
     *
     * @param array arguments
     * @return array
     */

    public function build($arguments = []) {
        $model = $this->model;
        return $model::build($arguments, $this->belongsToDetails);
    }




    ############################################################################
    #
    #  Scopes
    #
    ############################################################################


    /**
     * Desactive les scopes
     *
     * @return object
     */

    public function unscoped() {
        $this->unscoped = true;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Désactive un scope
     *
     * @param string scopeName
     * @return object
     */

    public function unscope($scopeName = null) {
        if (!$scopeName) {
            $this->scopesToExecute        = [];
        }
        else {
            foreach ($this->scopesToExecute as $index => $scopesToExecute) {
                if ($scopesToExecute['name'] == $scopeName) {
                    unset($this->scopesToExecute[$index]);
                    break;
                }
            }
        }
        return $this;
    }


    ############################################################################
    #
    #  Fusion
    #
    ############################################################################


    /**
     * Fusionne deux requêtes
     *
     * @param object object
     * @return object
     */

    public function merge(ERQuery $object) {
        $model = $object->model;
        foreach ($object->scopesToExecute as $scope) {
            call_user_func_array($model::getScopes()[$scope['name']], array_merge([$object], $scope['args']));
        }

        $this->resultColonnes       = array_merge_recursive($this->resultColonnes, $object->resultColonnes);
        $this->whereConditions      = array_merge_recursive($this->whereConditions, $object->whereConditions);
        $this->joinSources          = array_merge_recursive($this->joinSources, $object->joinSources);
        $this->joinWhereTables      = array_merge_recursive($this->joinWhereTables, $object->joinWhereTables);
        $this->joinWhereContraintes = array_merge_recursive($this->joinWhereContraintes, $object->joinWhereContraintes);
        $this->whereBinds           = array_merge_recursive($this->whereBinds, $object->whereBinds);
        $this->joinBinds            = array_merge_recursive($this->joinBinds, $object->joinBinds);
        $this->groupBy              = array_merge_recursive($this->groupBy, $object->groupBy);
        $this->havingConditions     = array_merge_recursive($this->havingConditions, $object->havingConditions);
        $this->havingBinds          = array_merge_recursive($this->havingBinds, $object->havingBinds);
        $this->orderBy              = array_merge_recursive($this->orderBy, $object->orderBy);
        return $this;
    }



    ############################################################################
    #
    #  Gestion des alias
    #
    ############################################################################


    /**
     * Alias de tableAlias
     *
     * @param string alias
     * @return object
     */

    public function alias($alias) {
        return $this->tableAlias($alias);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini un alias pour la table du modèle
     *
     * @param string alias
     * @return object
     */

    public function tableAlias($alias) {
        if (!is_string($alias)) {
            throw new ERException(get_class($this->model) . '::tableAlias() expects paramter 1 to be a string, ' . gettype($alias) . ' given');
        }

        if ($alias) {
            $this->tableAlias = $alias;
        }
        return $this;
    }



    ############################################################################
    #
    #  ReadOnly / Distinct / RawQuery / From
    #
    ############################################################################


    /**
     * Active le mode lecture seule sur le modèle
     *
     * @return object
     */

    public function readOnly() {
        $this->model->setReadOnly();
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute l'instruction DISTINCT à la requête
     *
     * @return object
     */

    public function distinct() {
        $this->distinct = true;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Définit une requête SQL brute
     *
     * @param string query
     * @return object
     */

    public function rawQuery($query) {
        if (!is_string($query)) {
            throw new ERException(get_class($this->model) . '::rawQuery() expects paramter 1 to be a string, ' . gettype($query) . ' given');
        }

        $this->rawQuery = $query;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini l'instruction FROM
     *
     * @param string from
     * @return object
     */

    public function from($from) {
        if (!is_string($from)) {
            throw new ERException('EasyRecord::from() expects paramter 1 to be a string, ' . gettype($from) . ' given');
        }

        $this->fromExpr = $from;
        return $this;
    }



    ############################################################################
    #
    #  Fonctions d'agrégation
    #
    ############################################################################


    /**
     * Défini l'instruction COUNT
     *
     * @param string colonne
     */

    public function count($colonne = '*') {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini l'instruction MAX
     *
     * @param string colonne
     */

    public function max($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini l'instruction MIN
     *
     * @param string colonne
     */

    public function min($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini l'instruction AVG
     *
     * @param string colonne
     */

    public function avg($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }


    // -------------------------------------------------------------------------

    /**
     * Défini l'instruction SUM
     *
     * @param string colonne
     */

    public function sum($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }


    ############################################################################
    #
    #  Select
    #
    ############################################################################

    /**
     * Ajoute les colonnes à la liste de colonnes de la requête
     *
     * @exemple select(array('colonne' => 'alias', 'colonne2', 'colonne3' => 'alias2'), 'colonne4', 'colonne5');
     * @exemple select('colonne', 'colonne2', 'colonne3');
     * @exemple select(array('colonne', 'colonne2', 'colonne3'), 'colonne4', 'colonne5');
     *
     * @return \EasyRecord
     */

    public function select() {
        $colonnes = func_get_args();
        if (!empty($colonnes)) {
            $colonnes = $this->normaliserSelectPlusieursColonnes($colonnes);
            foreach ($colonnes as $alias => $colonne) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                else {
                    $this->selectAliases[] = $alias;
                }
                $this->selectSingle($colonne, $alias);
            }
        }

        if ($this->autoSelectChild) {
            $model = $this->model;

            foreach ($model::getIdentifier($model::READ_TO_ARRAY) as $col) {
                $this->selectSingle($col);
            }

            $relations = $this->model->relations();

            foreach ($relations::getForeignKeys($model, $model::belongsTo()) as $keys) {
                if(is_array($keys)) {
                    foreach ($keys as $key => $value) {
                        $this->selectSingle($value);
                    }
                } else {
                    $this->selectSingle($keys);
                }
            }
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini si les clés des relations doivent être sélectionnées
     *
     * @param boolean value
     * @return object
     */

    public function autoSelectChild($value = true) {
        $this->autoSelectChild = (bool) $value;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini une expression SELECT
     *
     * @return object
     */

    public function selectExpr() {
        $colonnes = $this->normaliserSelectPlusieursColonnes(func_get_args());
        foreach ($colonnes as $alias => $colonne) {
            if (is_numeric($alias)) {
                $alias = null;

                if (stripos($colonne, ' AS ') !== false) {
                    $str = explode(' AS ', $colonne);
                    array_shift($str);

                    foreach ($str as $value) {
                        if (preg_match('/^[a-zA-Z\_\d]+/', $value, $aliasSelect)) {
                            $this->selectAliases[] = $aliasSelect[0];
                        }
                    }
                }
            } else {
                $this->selectAliases[] = $alias;
            }
            $this->ajoutResultatColonne($colonne, $alias);
        }
        return $this;
    }


    ############################################################################
    #
    #  Where
    #
    ############################################################################


    /**
     * Construit une instruction WHERE
     *
     * @return object
     */

    public function where() {
        $args = func_get_args();
        if (count($args) == 1 && $args[0]) {
            return $this->ajoutWhere($args[0]);
        }
        elseif (count($args) > 1) {
            if (strpos($args[0], '?') === false && count($args) == 2) {
                if (is_array($args[1])) {
                    if (count($args[1]) > 1) {
                        return $this->whereIn($args[0], $args[1]);
                    }
                    else {
                        return $this->ajoutWhereSimple($args[0], '=', $args[1][0]);
                    }
                }
                else {
                    if ($args[1] === null) {
                        return $this->whereNull($args[0]);
                    }
                    else {
                        return $this->ajoutWhereSimple($args[0], '=', $args[1]);
                    }
                }
            } else {
                $first_arg = array_shift($args);
                foreach ($args as $arg) {
                    $this->whereBinds['types'][]  = 's';
                    $this->whereBinds['values'][] = $arg;
                }
                return $this->ajoutWhere($first_arg);
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE <> ou IS NOT NULL
     *
     * @return object
     */

    public function whereNot($colonne, $valeur) {
        if (is_array($valeur)) {
            if (count($valeur)) {
                return $this->whereNotIn($colonne, $valeur);
            }
        }
        else {
            if ($valeur === null) {
                return $this->whereNotNull($colonne);
            }
            else {
                return $this->whereNotEqual($colonne, $valeur);
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE <>
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereNotEqual($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<>', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE LIKE
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereLike($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, 'LIKE', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE NOT LIKE
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereNotLike($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, 'NOT LIKE', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE >
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereGt($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '>', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE <
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereLt($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE >=
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereGte($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '>=', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE <=
     *
     * @param string colone
     * @param mixed valeur
     * @return object
     */

    public function whereLte($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<=', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE IN()
     *
     * @param string colonne
     * @param array valeurTab
     * @return object
     */

    public function whereIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne);
            $stringIn = $this->addWhereBinds($colonne, $valeurTab);
            return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IN (' . $stringIn . ')');
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE NOT IN()
     *
     * @param string colonne
     * @param array valeurTab
     * @return object
     */

    public function whereNotIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne);
            $stringIn = $this->addWhereBinds($colonne, $valeurTab);
            return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' NOT IN (' . $stringIn . ')');
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE IS NULL
     *
     * @param string colonne
     * @return object
     */

    public function whereNull($colonne) {
        $colonne = $this->formatColumn($colonne);
        return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IS null');
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction WHERE ID NOT NULL
     *
     * @param string colonne
     * @return object
     */

    public function whereNotNull($colonne) {
        $colonne = $this->formatColumn($colonne);
        return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IS NOT null');
    }



    ############################################################################
    #
    #  Having
    #
    ############################################################################


    /**
     * Instruction HAVING
     *
     * @param string colonne
     * @return object
     */

    public function having() {
        $args = func_get_args();
        if (count($args) == 1) {
            return $this->ajoutHaving($args[0]);
        }
        elseif (count($args) > 1) {
            if (ctype_alpha($args[0]) && count($args) == 2) {
                if (is_array($args[1])) {
                    if (count($valeurTab)) {
                        return $this->havingIn($args[0], $args[1]);
                    }
                } else {
                    if ($args[1] === null) {
                        return $this->havingNull($args[0]);
                    }
                    else {
                        return $this->ajoutHavingSimple($args[0], '=', $args[1]);
                    }
                }
            } else {
                $first_arg = array_shift($args);
                foreach ($args as $arg) {
                    $this->havingBinds['types'][]  = 's';
                    $this->havingBinds['values'][] = $arg;
                }
                return $this->ajoutHaving($first_arg);
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING <> ou HAVING NOT NULL
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingNot($colonne, $valeur) {
        if (is_array($valeur)) {
            if (count($valeur)) {
                return $this->havingNotIn($colonne, $valeur);
            }
        }
        else {
            if ($valeur === null) {
                return $this->havingNotNull($valeur);
            }
            else {
                return $this->havingNotEqual($colonne, $valeur);
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING <>
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingNotEqual($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<>', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING LIKE
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingLike($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, 'LIKE', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING NOT LIKE
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingNotLike($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, 'NOT LIKE', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING >
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingGt($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '>', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING <
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingLt($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING >=
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingGte($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '>=', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING <=
     *
     * @param string colonne
     * @param mixed valeur
     * @return object
     */

    public function havingLte($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<=', $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING IN()
     *
     * @param string colonne
     * @param mixed valeurTab
     * @return object
     */

    public function havingIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne, false);
            $stringIn = $this->addHavingBinds($colonne, $valeurTab);
            return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IN (' . $stringIn . ')');
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING NOT IN()
     *
     * @param string colonne
     * @param mixed valeurTab
     * @return object
     */

    public function havingNotIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne, false);
            $stringIn = $this->addHavingBinds($colonne, $valeurTab);
            return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' NOT IN (' . $stringIn . ')');
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING IS NULL
     *
     * @param string colonne
     * @return object
     */

    public function havingNull($colonne) {
        $colonne = $this->formatColumn($colonne, false);
        return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IS null');
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction HAVING IS NOT NULL
     *
     * @param string colonne
     * @return object
     */

    public function havingNotNull($colonne) {
        $colonne = $this->formatColumn($colonne, false);
        return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IS NOT null');
    }



    ############################################################################
    #
    #  OrderBy
    #
    ############################################################################


    /**
     * Instruction ORDER BY
     *
     * @return object
     */

    public function orderBy() {
        $args       = func_get_args();
        $orderByStr = null;
        foreach ($args as $order) {
            if ($order) {
                $order = trim($order);
                if (strcasecmp($order, 'ASC') == 0 || strcasecmp($order, 'DESC') == 0) {
                    end($this->orderBy);
                    $this->orderBy[key($this->orderBy)] .= ' ' . $order;
                }
                else {
                    $tabArgs = explode(',', $order);
                    foreach ($tabArgs as $tabArg) {
                        $tabArg = trim($tabArg);
                        if (preg_match('/^[A-Za-z0-9_]+$/', $tabArg)) {
                            $colonne         = $this->formatColumn($tabArg, false);
                            $this->orderBy[] = ERTools::quoteIdentifiant($colonne);
                        }
                        else {
                            $elementsEgal     = explode('=', $tabArg);
                            $elementsSpace    = explode(' ', $elementsEgal[0]);
                            $elementsSpace[0] = trim($elementsSpace[0]);
                            $elementsSpace[0] = $this->formatColumn($elementsSpace[0], false);
                            $elementsSpace[0] = ERTools::quoteIdentifiant($elementsSpace[0]);
                            $elementsEgal[0]  = implode(' ', $elementsSpace);
                            $tabArg           = implode('=', $elementsEgal);
                            $this->orderBy[]  = $tabArg;
                        }
                    }
                }
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction ORDER BY DESC
     *
     * @param string colonne
     * @return object
     */

    public function orderByDesc($colonne) {
        $this->orderBy($colonne, 'DESC');
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction ORDER BY ASC
     *
     * @param string colonne
     * @return object
     */

    public function orderByAsc($colonne) {
        $this->orderBy($colonne, 'ASC');
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction ORDER BY Custom
     *
     * @param string orderByExpr
     * @return object
     */

    public function orderByExpr($orderByExpr) {
        if ($orderByExpr) {
            $this->orderBy[] = $orderByExpr;
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Tri le jeu de résultat
     *
     * @return object
     */

    public function reorderBy() {
        $this->orderBy = [];
        return call_user_func_array(array($this, 'orderBy'), func_get_args());
    }


    ############################################################################
    #
    #  Limit / Offset / GroupBy
    #
    ############################################################################


    /**
     * Instruction LIMIT 0, limit
     *
     * @param int limit
     * @return object
     */

    public function limit($limit) {
        if ($limit) $this->limit = $limit;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction LIMIT offset, limit
     *
     * @param int offset
     * @return object
     */

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction LIMIT (page - 1) * nb, nb
     *
     * @param int page
     * @param int nb
     * @return object
     */

    public function paginate($page, $nb) {
        $this->offset = ($page - 1) * $nb;
        $this->limit  = $nb;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction GROUP BY
     *
     * @return object
     */

    public function groupBy() {
        $args = func_get_args();
        foreach ($args as $colonne) {
            $colonne         = $this->formatColumn($colonne, false);
            $this->groupBy[] = ERTools::quoteIdentifiant($colonne);
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction GROUP BY par expression
     *
     * @param string
     * @return object
     */

    public function groupByExpr($expr) {
        $this->groupBy[] = ERTools::quoteIdentifiant($expr);
        return $this;
    }


    ############################################################################
    #
    #  Execution des requêtes
    #
    ############################################################################


    /**
     * Défini l'indexation du tableau de résultat
     *
     * @param string index
     * @return object
     */

    public function index($index) {
        $this->index = $index;
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère le premier élément d'une requête
     *
     * @param int n
     * @return FALSE | array
     */

    public function first($n = 1) {
        $this->first = true;
        return $this->take($n);
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère le dernier élement d'une requête
     *
     * @param int n
     * @return FALSE | array
     */

    public function last($n = 1) {
        $this->last = true;
        return $this->take($n);
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère les n premiers éléments d'une requête
     *
     * @param int n
     * @return FALSE | array
     */

    public function take($n = 1) {
        if (!is_int($n)) {
            throw new ERException(get_class($this->model) . '::take() expects paramter 1 to be a string, ' . gettype($n) . ' given');
        }

        $this->limit($n);
        $lignes = $this->run();

        if (empty($lignes)) {
            if ($n == 1) {
                return false;
            }
            else {
                return [];
            }
        }
        if ($n == 1) {
            return $lignes[0];
        }
        return $lignes;
    }


    // -------------------------------------------------------------------------

    /**
     * Alias de toArray
     *
     * @return array
     */

    public function all() {
        return $this->toArray();
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les résultats d'une requête sous forme de tableau
     *
     * @return array
     */

    public function toArray() {
        $this->runQuery();
        return $this->results;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne une instance (ou un groupe) de modèle(s) en fonction de leur clé promaire
     *
     * @param array | mixed id
     * @return FALSE | object | array
     */

    public function find($id) {

        $model = $this->model;
        $pks = $model::getIdentifier($model::READ_TO_ARRAY);

        if(count($pks) == 1) {
            if(is_array($id)) {
                if(count($id) == 1) {
                    return $this->findCacheFull(reset($id));
                } else {
                    return $this->whereIn(reset($pks), $id)->all();
                }
            }
            return $this->findCacheFull($id);
        }
        else {
            if(is_array($id)) {
                if(is_array(reset($id)[0])) {
                    return $this->whereIn($pks, $id)->all();
                }
                return $this->findCacheFull($id);
            } else {
                throw new ERException(get_class($this->model) . '::find() wrong identifier. Expected array for multiple primary keys.');
            }
        }
    }

    #############################################################################
    #
    #  Jointures
    #
    #############################################################################


    /**
     * Effectue une jointure en utilisant une relation potentielle
     *
     * @param string
     * @return object
     */

    public function with($with) {
        $model = $this->model;

        $path = explode('::', $with);
        $steps = [];
        $currentRelation = $this->model;

        foreach ($path as $step) {
            if(in_array($step, $model::belongsTo()) || array_key_exists($step, $model::belongsTo()) || in_array($step, $model::hasOne()) || array_key_exists($step, $model::hasOne())) {

                $steps[] = $step;

                $WithClass = $model->getRelationClassName($step);

                $cleEtrangere = $model->getRelationKeys($step);

                $currentRelation->preparePotentialRelation($step);

                $currentRelation = $currentRelation->getPotentialRelation($step);

                // Seulement si pas de select et si premier with
                if(count($steps) == 1) {
                    $this->selectExpr($model::table() . '.*');
                }

                foreach($WithClass::mappingConfig()['fields'] as $name => $config) {
                    $this->selectExpr($WithClass::table() . '.' . $name . ' AS _' . implode('_', $steps) . '_' . $name);
                }

                // Construire la relation en join
                $conditions = [];
                foreach($cleEtrangere as $key => $foreign_key) {
                    $conditions[] = ['identifier' => $WithClass::table() . '.' . $foreign_key, 'foreign_key' => $model::table() . '.' . $key];
                }

                $this->join($WithClass::database() . '.' . $WithClass::table(), $conditions);
                $model = new $WithClass;

            } else {
                throw new ERException(get_class($this->model) . '::with() wrong relation. Expected belongsTo or hasOne.');
            }
        }

        return $this;
    }


    /**
     * Effectue un groupe de jointures LEFT JOIN
     *
     * @return object
     */

    public function includes() {
        $args             = func_get_args();
        $key              = array_shift($args);
        $alias            = null;
        $aliasConcatTable = null;
        $condition        = [];
        $firstAlias       = true;

        foreach ($args as $arg) {
            if (!is_array($arg)) {
                if ($firstAlias) {
                    $firstAlias = false;
                    $alias      = $arg;
                }
                else {
                    $aliasConcatTable = $arg;
                }
            }
            else {
                $condition = $arg;
            }
        }

        foreach ($this->joinsIncludes($key, $alias, $aliasConcatTable, $condition) as $detail) {
            $this->leftJoin($detail['table'], $detail['constraints'], $detail['alias']);
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction JOIN d'un groupe
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @return object
     */

    public function joins($key, $alias = null, $aliasConcatTable = null) {
        foreach ($this->joinsIncludes($key, $alias, $aliasConcatTable) as $detail) {
            $this->join($detail['table'], $detail['constraints'], $detail['alias']);
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction JOIN
     *
     * @return object
     */

    public function join() {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->joinSources[] = 'JOIN ' . $args[0];
            return $this;
        }
        elseif (count($args) > 1) {
            $tableAlias = isset($args[2]) ? $args[2] : null;
            return $this->ajoutJoinSource('', $args[0], $args[1], $tableAlias);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction LEFT OUTER JOIN
     *
     * @return object
     */

    public function leftJoin() {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->joinSources[] = 'LEFT OUTER JOIN ' . $args[0];
            return $this;
        }
        elseif (count($args) > 1) {
            $tableAlias = isset($args[2]) ? $args[2] : null;
            return $this->ajoutJoinSource('LEFT OUTER', $args[0], $args[1], $tableAlias);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction RIGHT OUTER JOIN
     *
     * @return object
     */

    public function rightJoin() {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->joinSources[] = 'RIGHT OUTER JOIN ' . $args[0];
            return $this;
        }
        elseif (count($args) > 1) {
            $tableAlias = isset($args[2]) ? $args[2] : null;
            return $this->ajoutJoinSource('RIGHT OUTER', $args[0], $args[1], $tableAlias);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction FULL OUTER JOIN
     *
     * @return object
     */

    public function fullJoin() {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->joinSources[] = 'FULL OUTER JOIN ' . $args[0];
            return $this;
        }
        elseif (count($args) > 1) {
            $tableAlias = isset($args[2]) ? $args[2] : null;
            return $this->ajoutJoinSource('FULL OUTER', $args[0], $args[1], $tableAlias);
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Instruction INNER JOIN
     *
     * @return object
     */

    public function innerJoin() {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->joinSources[] = 'INNER JOIN ' . $args[0];
            return $this;
        }
        elseif (count($args) > 1) {
            $tableAlias = isset($args[2]) ? $args[2] : null;
            return $this->ajoutJoinSource('INNER', $args[0], $args[1], $tableAlias);
        }
    }


    ############################################################################
    #
    #   Utilities
    #
    ############################################################################


    /**
     * Recherche une instance de modèle en cache ou en BDD
     *
     * @param mixed id
     * @return FALSE | object
     */

    private function findCacheFull($id) {

        $result    = false;
        $model     = $this->model;
        $className = get_class($model);

        $isCache = $model::cacheActivation() == 'full' && $this->cache;

        if ($isCache) {
            if(is_array($id)) {
                $id = implode('_', $id);
            }
            $result = ERCache::getInstance()->nsGet('EasyRecordCache', $className . '_' . $id . $model::cacheActivation() . $model::cacheTime());
        }

        if (!$result) {
            if(is_array($id)) {
                foreach ($id as $col => $value) {
                    $this->where($col, $value);
                }
            } else {
                $this->where($model::getIdentifier($model::READ_TO_STRING), $id);
            }

            $result = $this->take();

            if ($isCache && $result) {
                if(is_array($id)) {
                    $id = implode('_', $id);
                }
                ERCache::getInstance()->nsSet('EasyRecordCache', $className . '_' . $id . $model::cacheActivation() . $model::cacheTime(), $result, $model::cacheTime());
            }
        }

        return $result;
    }


    // -------------------------------------------------------------------------

    /**
     * Uniformise une structure multi-colonne
     *
     * @param array colonnes
     * @return array
     */

    private function normaliserSelectPlusieursColonnes($colonnes) {
        $return = [];
        foreach ($colonnes as $colonne) {
            if (is_array($colonne)) {
                foreach ($colonne as $key => $value) {
                    if (!is_numeric($key)) {
                        $return[$value] = $key;
                    }
                    else {
                        $return[]       = $value;
                    }
                }
            } else {
                $return[] = $colonne;
            }
        }
        return $return;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit une colonne de sélection
     *
     * @param string expr
     * @param string alias
     * @return object
     */

    private function ajoutResultatColonne($expr, $alias = null) {
        if ($alias !== null) {
            $expr .= ' AS ' . ERTools::quoteIdentifiant($alias);
        }

        if (array_search($expr, $this->resultColonnes) === false) {
            $this->resultColonnes[] = $expr;
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une condition WHERE
     *
     * @param string fragement
     * @return object
     */

    private function ajoutWhere($fragment) {
        return $this->ajoutCondition('where', $fragment);
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une condition WHERE préformatée
     *
     * @param string colonne
     * @param string separateur
     * @param string valeur
     * @return object
     */

    private function ajoutWhereSimple($colonne, $separateur, $valeur) {
        return $this->ajoutConditionSimple('where', $colonne, $separateur, $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure (JOIN) conditionnelle
     *
     * @param string joinOperateur
     * @param string table
     * @param string contrainte
     * @param string tableAlias
     * @return object
     */

    private function ajoutJoinSource($joinOperateur, $table, $contraintes, $tableAlias = null) {
        $model         = $this->model;
        $joinOperateur = trim($joinOperateur . ' JOIN');
        $table         = ERTools::quoteIdentifiant($table);

        if ($tableAlias !== null) {
            $tableAlias = ERTools::quoteIdentifiant($tableAlias);
            $table .= ' ' . $tableAlias;
        }

        $constraints = [];

        foreach ($contraintes as $contrainte) {
            if (!$this->joinFirstTable) {
                if (substr_count($contrainte['identifier'], '.') == 1) {
                    $this->joinFirstTable = explode('.', $contrainte['identifier'])[0];
                }
                else {
                    $staticTable          = $model::table();
                    $tableAlias           = $this->tableAlias ? $this->tableAlias : $staticTable;
                    $this->joinFirstTable = ERTools::quoteIdentifiant($tableAlias);
                }
            }

            $premiereColonne     = $this->formatColumn($contrainte['foreign_key']);
            $secondeColonne      = $this->formatColumn($contrainte['identifier']);
            $constraints[]       = ERTools::quoteIdentifiant($premiereColonne) . ' = ' . ERTools::quoteIdentifiant($secondeColonne);

            if(isset($contrainte['condition']) && is_array($contrainte['condition'])) {
                foreach ($contrainte['condition'] as $value) {
                    $constraints[] = $value;
                }
            }
        }

        $this->joinSources[] = $joinOperateur . ' ' . $table . ' ON ' . implode(' AND ', $constraints);

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit une variable à la condition WHERE
     *
     * @param string colonne
     * @param array valeurTab
     * @return string
     */

    private function addWhereBinds($colonne, $valeurTab) {
        $model    = $this->model;
        $aryIMark = [];
        $type     = 's';

        if (array_key_exists($colonne, $model::mapping())) {
            $type = $model::mapping()[$colonne]['type'][0];
        }

        foreach ($valeurTab as $value) {
            $this->whereBinds['types'][]  = $type;
            $this->whereBinds['values'][] = $value;
            $aryIMark[]                   = '?';
        }
        return implode(', ', $aryIMark);
    }


    // -------------------------------------------------------------------------

    /**
     * Construit une variable à la condition HAVING
     *
     * @param string colonne
     * @param array valeurTab
     * @return string
     */

    private function addHavingBinds($colonne, $valeurTab) {
        $model    = $this->model;
        $aryIMark = [];
        $type     = 's';

        if (array_key_exists($colonne, $model::mapping())) {
            $type = $model::mapping()[$colonne]['type'][0];
        }

        foreach ($valeurTab as $value) {
            $this->havingBinds['types'][]  = $type;
            $this->havingBinds['values'][] = $value;
            $aryIMark[]                    = '?';
        }
        return implode(', ', $aryIMark);
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une condition HAVING préformatée
     *
     * @param string colonne
     * @param string separateur
     * @param mixed valeur
     * @return object
     */

    private function ajoutHavingSimple($colonne, $separateur, $valeur) {
        return $this->ajoutConditionSimple('having', $colonne, $separateur, $valeur);
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une sélection de colonne
     *
     * @param string colonne
     * @param string alias
     * @return object
     */

    private function selectSingle($colonne, $alias = null) {
        if ($colonne !== '*') $colonne = $this->formatColumn($colonne);
        return $this->ajoutResultatColonne(ERTools::quoteIdentifiant($colonne), $alias);
    }


    // -------------------------------------------------------------------------

    /**
     * Construit une agrégation
     *
     * @param string fonctionSql
     * @param string colonne
     * @return object
     */

    private function appelFonctionDbAgregat($fonctionSql, $colonne) {
        $fonctionSql = strtoupper($fonctionSql);

        if ('*' != $colonne) {
            $colonne = ERTools::quoteIdentifiant($colonne);
        }

        $this->selectExpr([$fonctionSql . '(' . $colonne . ')' => $fonctionSql]);
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une codition HAVING
     *
     * @param string fragent
     */

    private function ajoutHaving($fragment) {
        return $this->ajoutCondition('having', $fragment);
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une condition préformatée
     *
     * @param string type
     * @param string colonne
     * @param string separateur
     * @param mixed valeur
     * @return object
     */

    private function ajoutConditionSimple($type, $colonne, $separateur, $valeur) {
        $model                  = $this->model;
        $colonneOriginale       = $colonne;
        $colonne                = $this->formatColumn($colonne, ($type != 'having'));
        $type_colonne           = 's';

        if (array_key_exists($colonneOriginale, $model::mapping())) {
            $type_colonne = $model::mapping()[$colonneOriginale]['type'][0];
        }

        $bindType               = $type . 'Binds';
        $arrayBinds             = $this->$bindType;
        $arrayBinds['types'][]  = $type_colonne;
        $arrayBinds['values'][] = $valeur;
        $this->$bindType        = $arrayBinds;

        return $this->ajoutCondition($type, ERTools::quoteIdentifiant($colonne) . ' ' . $separateur . ' ?');
    }


    // -------------------------------------------------------------------------

    /**
     * Ajout d'une condition
     *
     * @param string type
     * @param string fragment
     * @return object
     */

    private function ajoutCondition($type, $fragment) {
        $conditionsType = $type . 'Conditions';
        array_push($this->$conditionsType, '(' . $fragment . ')');
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure sur une relation
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param array condition
     * @return object
     */

    private function joinsIncludes($key, $alias, $aliasConcatTable = null, $condition = []) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;

        if (in_array($string, $model::hasMany()) || array_key_exists($string, $model::hasMany())) {
            return $this->joinsIncludesHasMany($key, $alias, $aliasConcatTable, $condition);
        }
        elseif (in_array($string, $model::hasOne()) || array_key_exists($string, $model::hasOne())) {
            return $this->joinsIncludesHasOne($key, $alias, $aliasConcatTable, $condition);
        }
        elseif (in_array($string, $model::belongsTo()) || array_key_exists($string, $model::belongsTo())) {
            return $this->joinsIncludesBelongsTo($key, $alias, $aliasConcatTable, $condition);
        }
        elseif (in_array($string, $model::hasAndBelongsToMany()) || array_key_exists($string, $model::hasAndBelongsToMany())) {
            return $this->joinsIncludesHasAndBelongsToMany($key, $alias, $aliasConcatTable, $condition);
        }

        throw new ERException('EasyRecord::joinsIncludes() parameter 1 ('.$key.') is not a known relationship', 1);
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure sur une relation 1 / n au travers d'une table de concaténation
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param string condition
     * @param string infosArray
     * @param string hasMany
     * @param string tableAlias
     * @return object
     */

    private function joinsIncludesHasManyThrough($key, $alias, $aliasConcatTable, $condition, $infosArray, $hasMany, $tableAlias) {
        $model     = $this->model;
        $through   = $infosArray['through'];

        if ($through[strlen($through) - 1] == 's') {
            $through = substr($through, 0, strlen($through) - 1);
        }

        $belongsTo = get_class($model);

        if (array_key_exists($belongsTo, $through::belongsTo())) {
            $infosArray = $through::belongsTo()[$belongsTo];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangereBelongsTo = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $belongsTo = $model->getNamespace() . $infosArray['class_name'];
            }

            if (!isset($cleEtrangereBelongsTo) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangereBelongsTo = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
            }
        }
        if (!isset($cleEtrangereBelongsTo)) {
            $cleEtrangereBelongsTo = $model::getIdentifier($model::READ_TO_ARRAY);
        }

        if (array_key_exists($key, $through::hasMany())) {
            $infosArray = $through::hasMany()[$key];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangereHasMany = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $hasMany = $model->getNamespace() . $infosArray['class_name'];
            }

            if (!isset($cleEtrangereHasMany) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangereHasMany = $hasMany::hasMany()[$infosArray['inverse_of']]['foreign_key'];
            }
        }

        if (!isset($cleEtrangereHasMany)) {
            $cleEtrangereHasMany = $hasMany::getIdentifier($hasMany::READ_TO_ARRAY);
        }

        if (!$alias) {
            $alias = $hasMany::table();
        }

        if (!$aliasConcatTable) {
            $aliasConcatTable = $through::table();
        }

        $throughDatabase = $through::database();

        if(!is_array($cleEtrangereBelongsTo)) {
            $cleEtrangereBelongsTo = [$cleEtrangereBelongsTo];
        }

        if(!is_array($cleEtrangereHasMany)) {
            $cleEtrangereHasMany = [$cleEtrangereHasMany];
        }

        $constraintsBelongsTo = [];
        $constraintsHasMany   = [];

        foreach ($cleEtrangereBelongsTo as $key) {
            $constraintsBelongsTo[] = [
                'foreign_key' => $aliasConcatTable . '.' . $key,
                'identifier'   => $tableAlias . '.' . $key,
                'condition'    => []
            ];
        }

        foreach ($cleEtrangereHasMany as $key) {
            $constraintsHasMany[] = [
                'foreign_key' => $aliasConcatTable . '.' . $key,
                'identifier'   => $alias . '.' . $key,
                'condition'    => $condition
            ];
        }

        return [
                 ['table'       => $throughDatabase . '.' . $through::table(),
                 'alias'        => $aliasConcatTable,
                 'constraints'  => $constraintsBelongsTo
                 ],
                 ['table'       => $throughDatabase . '.' . $hasMany::table(),
                 'alias'        => $alias,
                 'constriants'  => $constraintsHasMany
                ]
            ];
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue uen jointure sur une relation 1 / n
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param string condition
     * @return object
     */

    private function joinsIncludesHasMany($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasMany     = $model->getNamespace() . $string;

        if ($hasMany[strlen($hasMany) - 1] == 's') {
            $hasMany = substr($hasMany, 0, strlen($hasMany) - 1);
        }

        if (array_key_exists($string, $model::hasMany())) {
            $infosArray = $model::hasMany()[$string];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $hasMany = $model->getNamespace() . $infosArray['class_name'];
            }

            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangere = $hasMany::belongsTo()[$infosArray['inverse_of']]['foreign_key'];
            }

            if (array_key_exists('through', $infosArray)) {
                return $this->joinsIncludesHasManyThrough($key, $alias, $aliasConcatTable, $condition, $infosArray, $hasMany, $tableAlias);
            }
        }
        if (!isset($cleEtrangere)) {
            $cleEtrangere = $model::getIdentifier($model::READ_TO_ARRAY);
        }

        if (!$alias) {
            $alias = $hasMany::table();
        }

        if(!is_array($cleEtrangere)) {
            $cleEtrangere = [$cleEtrangere];
        }

        $constraints = [];

        foreach ($cleEtrangere as $identifier => $key) {
            $constraints[] = [
                'foreign_key' => $alias . '.' . $key,
                'identifier'   => $tableAlias . '.' . $identifier,
                'condition'    => $condition
            ];
        }

        return [
                ['table'      => $hasMany::database() . '.' . $hasMany::table(),
                'alias'       => $alias,
                'constraints' => $constraints
                ]
            ];
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure sur une relation modèle / 1
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param string condition
     * @return object
     */

    private function joinsIncludesHasOne($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasOne      = $model->getNamespace() . $string;

        if (array_key_exists($string, $model::hasOne())) {
            $infosArray   = $model::hasOne()[$string];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $hasOne = $model->getNamespace() . $infosArray['class_name'];
            }

            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangere = $hasOne::belongsTo()[$infosArray['inverse_of']]['foreign_key'];
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $model::getIdentifier($model::READ_TO_ARRAY);
        }

        if (!$alias) {
            $alias = $hasOne::table();
        }

        if(!is_array($cleEtrangere)) {
            $cleEtrangere = [$cleEtrangere];
        }

        $constraints = [];

        foreach ($cleEtrangere as $identifier => $key) {
            $constraints[] = [
                'foreign_key' => $alias . '.' . $key,
                'identifier' => $tableAlias . '.' . $identifier,
                'condition' => $condition
            ];
        }

        return [
                ['table' => $hasOne::database() . '.' . $hasOne::table(),
                'alias' => $alias,
                'constraints' => $constraints
                ]
            ];
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure sur une relation 1 / modèle
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param string condition
     * @return object
     */

    private function joinsIncludesBelongsTo($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $belongsTo   = $model->getNamespace() . $string;

        if (array_key_exists($string, $model::belongsTo())) {
            $infosArray   = $model::belongsTo()[$string];

            if (array_key_exists('foreign_key', $infosArray)) {
                $cleEtrangere = $infosArray['foreign_key'];
            }

            if (array_key_exists('class_name', $infosArray)) {
                $belongsTo = $model->getNamespace() . $infosArray['class_name'];
            }

            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray)) {
                $cleEtrangere = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
            }
        }

        if (!isset($cleEtrangere)) {
            $cleEtrangere = $belongsTo::getIdentifier($belongsTo::READ_TO_ARRAY);
        }

        if (!$alias) {
            $alias = $belongsTo::table();
        }

        if(!is_array($cleEtrangere)) {
            $cleEtrangere = [$cleEtrangere];
        }

        $constraints = [];

        foreach ($cleEtrangere as $identifier => $key) {
            $constraints[] = [
                'foreign_key' => $alias . '.' . $identifier,
                'identifier'   => $tableAlias . '.' . $key,
                'condition'    => $condition
            ];
        }

        return [
                ['table'      => $belongsTo::database() . '.' . $belongsTo::table(),
                'alias'       => $alias,
                'constraints' => $constraints
                ]
            ];
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une jointure sur une relation m / n
     *
     * @param string key
     * @param string alias
     * @param string aliasConcatTable
     * @param string condition
     * @return object
     */

    private function joinsIncludesHasAndBelongsToMany($key, $alias, $aliasConcatTable, $condition) {
        $model               = $this->model;
        $string              = ucfirst($key);
        $staticTable         = $model::table();
        $tableAlias          = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasAndBelongsToMany = $model->getNamespace() . $string;

        if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany) - 1] == 's') {
            $hasAndBelongsToMany = substr($hasAndBelongsToMany, 0, strlen($hasAndBelongsToMany) - 1);
        }

        $database = $model::database();

        if (array_key_exists($string, $model::hasAndBelongsToMany())) {
            $infosArray = $model::hasAndBelongsToMany()[$string];

            if (array_key_exists('class_name', $infosArray)) {
                $hasAndBelongsToMany     = $model->getNamespace() . $infosArray['class_name'];
            }

            if (array_key_exists('inverse_of', $infosArray)) {
                $infosArray              = $hasAndBelongsToMany::hasAndBelongsToMany()[$infosArray['inverse_of']];
            }

            if (array_key_exists('association_foreign_key', $infosArray)) {
                $associationCleEtrangere = $infosArray['association_foreign_key'];
            }

            if (array_key_exists('foreign_key', $infosArray)) {
                $concatCleEtrangere      = $infosArray['foreign_key'];
            }

            if (array_key_exists('table', $infosArray)) {
                $concatTable             = $infosArray['table'];
            }

            if (array_key_exists('database', $infosArray)) {
                $database                = $infosArray['database'];
            }
        }

        if (!isset($associationCleEtrangere)) {
            $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier($hasAndBelongsToMany::READ_TO_ARRAY);
        }

        if (!isset($concatCleEtrangere)) {
            $concatCleEtrangere = $model::getIdentifier($model::READ_TO_ARRAY);
        }

        $habtmTable = $hasAndBelongsToMany::table();
        $objetTable = $hasAndBelongsToMany::database() . '.' . $habtmTable;

        if (!isset($concatTable)) {
            if ($habtmTable < $staticTable){
                $concatTable = $habtmTable . '_' . $staticTable;
            }
            else {
                $concatTable = $staticTable . '_' . $habtmTable;
            }
        }

        $concatTableDb = $database . '.' . $concatTable;

        if (!$alias) {
            $alias = $habtmTable;
        }

        if (!$aliasConcatTable) {
            $aliasConcatTable = $concatTable;
        }

        if(!is_array($associationCleEtrangere)) {
            $associationCleEtrangere = [$associationCleEtrangere];
        }

        if(!is_array($concatCleEtrangere)) {
            $concatCleEtrangere = [$concatCleEtrangere];
        }

        $constraintsAssociation = [];
        $constraintsConcat      = [];

        foreach ($associationCleEtrangere as $identifier => $key) {

            if($identifier == 0) {
                $identifier = $key;
            }

            $constraintsAssociation[] = [
                'foreign_key' => $aliasConcatTable . '.' . $key,
                'identifier' => $alias . '.' . $identifier,
                'condition' => $condition
            ];
        }

        foreach ($concatCleEtrangere as $identifier => $key) {

            if($identifier == 0) {
                $identifier = $key;
            }

            $constraintsConcat[] = [
                'foreign_key' => $aliasConcatTable . '.' . $key,
                'identifier' => $tableAlias . '.' . $identifier,
                'condition' => $condition
            ];
        }

        $out = [
                ['table' => $concatTableDb,
                'alias' => $aliasConcatTable,
                'constraints' => $constraintsConcat

                ],
                ['table' => $objetTable,
                'alias' => $alias,
                'constraints' => $constraintsAssociation
                ]
        ];
        return $out;
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue une sélection sur toutes les colonnes
     *
     * @return object
     */

    private function selectAll() {
        $colonnes = func_get_args();

        if (!empty($colonnes)) {
            $colonnes = $this->normaliserSelectPlusieursColonnes($colonnes);

            foreach ($colonnes as $colonne) {
                $this->selectSingle($colonne . '.*');
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Formatte un identifiant de colonne
     *
     * @param string colonne
     * @param boolean check
     * @return string
     */

    private function formatColumn($colonne, $check = true) {
        $model = $this->model;
        if (($check || !in_array($colonne, $this->selectAliases)) && strpos($colonne, '.') === false) {
            if ($this->tableAlias !== null) {
                $colonne = $this->tableAlias . '.' . $colonne;
            }
            else {
                $colonne = $model::table() . '.' . $colonne;
            }
        }
        return $colonne;
    }



    ############################################################################
    #
    #  Construction de requêtes
    #
    ############################################################################


    /**
     * Construction d'une requête SELECT
     *
     * @return string
     */

    private function buildSelect() {
        $this->bindParam = new ERBindParam();

        if ($this->rawQuery) {
            return $this->rawQuery;
        }

        $this->buildSelectStart();
        $this->buildFrom();
        $this->buildJoin();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildHaving();
        $this->buildOrderBy();
        $this->buildLimit();
        $this->buildOffset();

        return $this->sqlQuery;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit le début une requête SELECT
     *
     * @return object
     */

    private function buildSelectStart() {
        $model = $this->model;
        if (!$this->resultColonnes) {
            $tableAlias = $this->tableAlias ? $this->tableAlias : $model::table();
            $this->selectAll($tableAlias);
        }
        $resultColonnes = implode(', ', $this->resultColonnes);

        if ($this->distinct) {
            $resultColonnes = 'DISTINCT ' . $resultColonnes;
        }

        $this->sqlQuery = 'SELECT ' . $resultColonnes . ' FROM ';
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Constuit la partie FROM
     *
     * @return object
     */

    private function buildFrom() {

        // A from is given
        if ($this->fromExpr) {
            $this->sqlQuery .= $this->fromExpr;
        }
        // Default table
        else {
            $model = $this->model;

            $this->sqlQuery .= ERTools::quoteIdentifiant($model::database()) . '.' . ERTools::quoteIdentifiant($model::table());
            if ($this->tableAlias !== null) {
                $this->sqlQuery .= ' ' . ERTools::quoteIdentifiant($this->tableAlias);
            }
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Constuit les jointures
     *
     * @return object
     */

    private function buildJoin() {
        // Real Join
        if (count($this->joinSources)) {
            foreach ($this->joinSources as $value) {
                $this->sqlQuery .= ' ' . $value;
            }
            foreach ($this->joinBinds['values'] as $index => $value) {
                $this->bindParam->add($this->joinBinds['types'][$index], $value);
            }
        }

        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Constuit la condition WHERE
     *
     * @return object
     */

    private function buildWhere() {
        if (count($this->whereConditions)) {
            $whereConditions = implode(' AND ', $this->whereConditions);

            if (count($this->joinWhereTables)) {
                $this->sqlQuery .= ' AND ' . $whereConditions;
            }
            else {
                $this->sqlQuery .= ' WHERE ' . $whereConditions;
            }

            foreach ($this->whereBinds['values'] as $index => $value) {
                $this->bindParam->add($this->whereBinds['types'][$index], $value);
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Constuit la partie GROUP BY
     *
     * @return object
     */

    private function buildGroupBy() {
        if (count($this->groupBy)) {
            $this->sqlQuery .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit la partie HAVING
     *
     * @return object
     */

    private function buildHaving() {
        if (count($this->havingConditions)) {
            $this->sqlQuery .= ' HAVING ' . implode(' AND ', $this->havingConditions);
            foreach ($this->havingBinds['values'] as $index => $value) {
                $this->bindParam->add($this->havingBinds['types'][$index], $value);
            }
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit la partie ORDER BY
     *
     * @return object
     */

    private function buildOrderBy() {
        if (count($this->orderBy)) {
            $this->sqlQuery .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit la partie LIMIT
     *
     * @return object
     */

    private function buildLimit() {
        if ($this->limit !== null) {
            $this->sqlQuery .= ' LIMIT ' . $this->limit;
        }
        return $this;
    }


    // -------------------------------------------------------------------------

    /**
     * Construit la partie OFFSET
     *
     * @return object
     */

    private function buildOffset() {
        if ($this->offset !== null) {
            $this->sqlQuery .= ' OFFSET ' . $this->offset;
        }
        return $this;
    }



    ############################################################################
    #
    #  Méthodes magiques
    #
    ############################################################################


    /**
     * Gère les accès aux propriétés des relations
     *
     * @param string key
     * @return mixed
     */

    public function __get($key) {
        return $this->model->relations()->getRelations($key, $this->model, $this->tableAlias);
    }


    // -------------------------------------------------------------------------

    /**
     * Gère la création dynamique de propriétés
     *
     * @param string name
     * @param array arguments
     * @return mixed
     */

    public function __call($name, $arguments) {
        $model = $this->model;
        if (count($model::getScopes()) && array_key_exists($name, $model::getScopes())) {
            $this->scopesToExecute[] = ['name' => $name, 'args' => $arguments];
            return $this;
        }
        elseif (preg_match('/findBy([\w]*)/', $name, $outputArray)) {
            $string = Text::camelToUnderscore($outputArray[1]);
            return $this->where(strtoupper($string), $arguments[0])->take();
        }
        elseif (preg_match('/add([\w]*)/', $name, $outputArray) || preg_match('/remove([\w]*)/', $name, $outputArray)) {
            return $this->addRemoveRelation($name, $arguments, $outputArray);
        }
        throw new ERException('Call to undefined method ' . get_class($model) . '::' . $name . '()');
    }


    // -------------------------------------------------------------------------

    /**
     * Contruit une requête DELETE
     *
     * @return boolean
     */

    public function delete() {
        if ($this->belongsToDetails) {
            $modelName = $this->belongsToDetails['model'];
            $referer   = $modelName::find($this->belongsToDetails['referer']);
            return $referer->deleteHasMany(str_replace('Agendaweb\App\Models\\', '', get_class($this->model)) . 's');
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
     * Execute une requête
     *
     * @return array | object
     */

    private function run() {
        $model = $this->model;
        if (!$this->unscoped) {
            foreach ($this->scopesToExecute as $scope) {
                call_user_func_array($model::getScopes()[$scope['name']], array_merge([$this], $scope['args']));
            }
            $this->scopesToExecute = [];
        }
        if (empty($this->orderBy)) {
            if ($this->first) {
                foreach ($model::getIdentifier($model::READ_TO_ARRAY) as $value) {
                    $this->orderBy($value);
                }
            }
            elseif ($this->last) {
                foreach ($model::getIdentifier($model::READ_TO_ARRAY) as $value) {
                    $this->orderByDesc($value);
                }
            }
        }
        $this->sqlQuery = $this->buildSelect();

        $listeObjects = $this->getObjects();

        return $listeObjects;
    }


    // -------------------------------------------------------------------------

    /**
     * Extrait les instances dse modèles sélectionné par la requête
     *
     * @return array | object
     */

    private function getObjects() {
        $model        = $this->model;
        $listeObjects = [];
        $result       = ERTools::execute($this->sqlQuery, $this->bindParam);

        if($this->index === false) {
            $this->index = $model->getIdentifier($model::READ_TO_STRING);
        }

        $i = 0;
        while ($result->next()) {
            if(isset($result->row()[$this->index])) {
                $index = $result->row()[$this->index];
            } else {
                $index = $i;
            }


            if ($this->lightMode == 0) {
                $shape = $this->entity ? $this->entity : get_class($this->model);
                $listeObjects[$index] = self::instantiate($result->row(), $shape, $this->model->getReadOnly(), $this->model->getPotentialRelations());
            }
            elseif ($this->lightMode == 2) {
                $listeObjects[$index] = array_change_key_case($result->row());
            }
            elseif($this->lightMode == 1) {
                $listeObjects[$index] = $result->get($model::getIdentifier($model::READ_TO_STRING));
            }

            $i++;
        }
        return $listeObjects;
    }


    // -------------------------------------------------------------------------

    /**
     * Instancie un nouvel objet à partir du résultat d'une requête
     *
     * @param array record
     * @param string class
     * @param boolean readOnly
     * @return object
     */

    private static function instantiate($record, $class, $readOnly, $potential_relations) {
        $object = new $class(true);

        if($potential_relations) {
            foreach($potential_relations as $relation => $datas) {
                $object->setPotentialRelation($relation, $datas);
            }
        }

        if ($readOnly) {
            $object->readOnly();
        }

        foreach ($record as $attribut => $value) {
            $object->setAttribute($attribut, $value);
        }
        $object->notNew();
        return $object;
    }


    // -------------------------------------------------------------------------

    /**
     * Exécute une requête si elle n'a pas été exécutée
     */

    private function runQuery() {
        if (!$this->queryExecuted) {
            $this->results       = $this->run();
            $this->queryExecuted = true;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Réassige des relations
     *
     * @param string name
     * @param array arguments
     * @param array outputArray
     * @return boolean
     */

    private function addRemoveRelation($name, $arguments, $outputArray) {
        $model  = $this->model;
        $string = $outputArray[1];
        if (in_array($string, $model::hasAndBelongsToMany()) || array_key_exists($string, $model::hasAndBelongsToMany())) {
            if (count($arguments) == 1) {
                $hasAndBelongsToMany = $this->model->getNamespace() . $string;

                if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany) - 1] == 's') {
                    $hasAndBelongsToMany = substr($hasAndBelongsToMany, 0, strlen($hasAndBelongsToMany) - 1);
                }

                $database = $model::database();
                if (array_key_exists($string, $model::hasAndBelongsToMany())) {
                    $infosArray = $model::hasAndBelongsToMany()[$string];

                    if (array_key_exists('class_name', $infosArray)) {
                        $hasAndBelongsToMany = $this->model->getNamespace() . $infosArray['class_name'];
                    }

                    if (array_key_exists('inverse_of', $infosArray)) {
                        $infosArray = $hasAndBelongsToMany::hasAndBelongsToMany()[$infosArray['inverse_of']];
                    }

                    if (array_key_exists('association_foreign_key', $infosArray)) {
                        $associationCleEtrangere = $infosArray['association_foreign_key'];
                    }

                    if (array_key_exists('foreign_key', $infosArray)) {
                        $concatCleEtrangere = $infosArray['foreign_key'];
                    }

                    if (array_key_exists('table', $infosArray)) {
                        $concatTable = $infosArray['table'];
                    }

                    if (array_key_exists('database', $infosArray)) {
                        $database = $infosArray['database'];
                    }
                }

                if (!isset($associationCleEtrangere)) {
                    $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier();
                }

                if (!isset($concatCleEtrangere)) {
                    $concatCleEtrangere = $model::getIdentifier();
                }

                $staticTable = $model::table();
                $habtmTable  = $hasAndBelongsToMany::table();
                $objetTable  = $hasAndBelongsToMany::database() . '.' . $habtmTable;

                if (!isset($concatTable)) {
                    if ($habtmTable < $staticTable) {
                        $concatTable = $habtmTable . '_' . $staticTable;
                    }
                    else {
                        $concatTable = $staticTable . '_' . $habtmTable;
                    }
                }
                $concatTable  = $database . '.' . $concatTable;
                if (is_object($arguments[0])) {
                    $arguments[0] = $arguments[0]->getIdentifierValue();
                }

                $details = ['concatTable' => $concatTable, 'objectId' => $associationCleEtrangere, 'objectValue' => $arguments[0], 'thisId' => $concatCleEtrangere];
                if (preg_match('/add([\w]*)/', $name, $outputArray)) {
                    if (!array_key_exists($concatTable, $model->relations()->hasAndBelongsToManyToAdd)) {
                        $model->relations()->hasAndBelongsToManyToAdd[$concatTable] = [];
                    }
                    $model->relations()->hasAndBelongsToManyToAdd[$concatTable][$arguments[0]] = $details;
                } else {
                    if (!array_key_exists($concatTable, $model->relations()->hasAndBelongsToManyToRemove)) {
                        $model->relations()->hasAndBelongsToManyToRemove[$concatTable] = [];
                    }
                    $model->relations()->hasAndBelongsToManyToRemove[$concatTable][$arguments[0]] = $details;
                }
                return TRUE;
            } else {
                throw new ERException('EasyRecord::add and EasyRecord::remove on a hasAndBelongsToMany relationship expects exactly 1 parameter, 0 given', 1);
            }
        }
        elseif (array_key_exists($string, $model::hasMany()) && array_key_exists('through', $model::hasMany()[$string])) {
            if (count($arguments) >= 1) {
                $hasMany = $this->model->getNamespace() . $string;
                if ($hasMany[strlen($hasMany) - 1] == 's') {
                    $hasMany = substr($hasMany, 0, strlen($hasMany) - 1);
                }

                $through = $model::hasMany()[$string]['through'];
                if ($through[strlen($through) - 1] == 's') {
                    $through = substr($through, 0, strlen($through) - 1);
                }

                if (is_object($arguments[0])) {
                    $arguments[0] = $arguments[0]->getIdentifierValue();
                }

                if (preg_match('/add([\w]*)/', $name, $outputArray)) {
                    if (!array_key_exists($through, $model->relations()->hasManyToAdd)) {
                        $model->relations()->hasManyToAdd[$through] = [];
                    }
                    if (!array_key_exists(1, $arguments)) {
                        $arguments[1] = [];
                    }
                    $model->relations()->hasManyToAdd[$through][$arguments[0]] = ['objectClass' => $through, 'relName' => $hasMany, 'relValue' => $arguments[0], 'args' => $arguments[1]];
                } else {
                    if (!array_key_exists($through, $model->relations()->hasManyToRemove)) {
                        $model->relations()->hasManyToRemove[$through] = [];
                    }

                    $belongsTo = get_class($model);
                    if (array_key_exists($belongsTo, $through::belongsTo())) {
                        $infosArray = $through::belongsTo()[$belongsTo];

                        if (array_key_exists('foreign_key', $infosArray)) {
                            $cleEtrangereBelongsTo = $infosArray['foreign_key'];
                        }

                        if (array_key_exists('class_name', $infosArray)) {
                            $belongsTo             = $this->model->getNamespace() . $infosArray['class_name'];
                        }

                        if (!isset($cleEtrangereBelongsTo) && array_key_exists('inverse_of', $infosArray)) {
                            $cleEtrangereBelongsTo = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
                        }
                    }

                    if (!isset($cleEtrangereBelongsTo)) {
                        $cleEtrangereBelongsTo = $model::getIdentifier();
                    }

                    if (array_key_exists($hasMany, $through::belongsTo())) {
                        $infosArray = $through::belongsTo()[$hasMany];

                        if (array_key_exists('foreign_key', $infosArray)) {
                            $cleEtrangereHasMany = $infosArray['foreign_key'];
                        }

                        if (array_key_exists('class_name', $infosArray)) {
                            $hasMany             = $this->model->getNamespace() . $infosArray['class_name'];
                        }

                        if (!isset($cleEtrangereHasMany) && array_key_exists('inverse_of', $infosArray)) {
                            $cleEtrangereHasMany = $hasMany::hasMany()[$infosArray['inverse_of']]['foreign_key'];
                        }
                    }
                    if (!isset($cleEtrangereHasMany)) {
                        $cleEtrangereHasMany = $hasMany::getIdentifier();
                    }

                    $model->relations()->hasManyToRemove[$through][$arguments[0]] = ['objectClass' => $through, 'hasManyKey' => $cleEtrangereHasMany, 'belongsToKey' => $cleEtrangereBelongsTo, 'hasManyValue' => $arguments[0]];
                }
                return TRUE;
            } else {
                throw new ERException('EasyRecord::add and EasyRecord::remove on a hasMany relationship expects at least 1 parameter, 0 given', 1);
            }
        }
    }
}

/* End of file */