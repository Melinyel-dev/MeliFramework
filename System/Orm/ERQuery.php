<?php

namespace System\Orm;

use Cache;
use Orb\Helpers\Text;

class ERQuery implements \IteratorAggregate, \ArrayAccess {
    #####################################
    # Query

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
    // NOTE: TEMPORAIRE
    protected $autoSelectChild      = true;

    #####################################
    # Scopes
    protected $scopesToExecute        = [];
    protected $unscoped               = false;

    public function __construct($model) {
        $this->model = $model;
    }

    #####################################
    # IteratorAggregate, ArrayAccess

    public function getIterator() {
        $this->runQuery();
        return new \ArrayIterator($this->results);
    }

    public function offsetSet($offset, $value) {
        $this->runQuery();
        if (is_null($offset)) {
            $this->results[] = $value;
        }
        else {
            $this->results[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        $this->runQuery();
        return isset($this->results[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->results[$offset]);
    }

    public function offsetGet($offset) {
        $this->runQuery();
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
    }

    #####################################
    # Cache

    public function cache() {
        $this->cached = true;
        return $this;
    }

    public function noCache() {
        $this->cached = false;
        return $this;
    }

    #####################################
    # LightModes

    public function assocArray() {
        $this->lightMode = 2;
        return $this;
    }

    public function ids() {
        $model           = $this->model;
        $this->lightMode = 1;
        $tableAlias      = $this->tableAlias ? $this->tableAlias : $model::table();
        $this->select($tableAlias . '.' . $model::getIdentifier(false));
        return $this;
    }

    public function build($arguments = []) {
        $model = $this->model;
        return $model::build($arguments, $this->belongsToDetails);
    }

    #####################################
    # Scopes

    public function unscoped() {
        $this->unscoped = true;
        return $this;
    }

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

    #####################################
    # Merge

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

    #####################################
    # Alias

    public function alias($alias) {
        return $this->tableAlias($alias);
    }

    public function tableAlias($alias) {
        if (!is_string($alias))
                throw new ERException(get_class($this->model) . '::tableAlias() expects paramter 1 to be a string, ' . gettype($alias) . ' given');
        if ($alias) {
            $this->tableAlias = $alias;
        }
        return $this;
    }

    #####################################
    # ReadOnly/Distinct/RawQuery/From

    public function readOnly() {
        $this->model->setReadOnly();
        return $this;
    }

    public function distinct() {
        $this->distinct = true;
        return $this;
    }

    public function rawQuery($query) {
        if (!is_string($query))
                throw new ERException(get_class($this->model) . '::rawQuery() expects paramter 1 to be a string, ' . gettype($query) . ' given');
        $this->rawQuery = $query;
        return $this;
    }

    public function from($from) {
        if (!is_string($from))
                throw new ERException('EasyRecord::from() expects paramter 1 to be a string, ' . gettype($from) . ' given');
        $this->fromExpr = $from;
        return $this;
    }

    #####################################
    # Agregate functions

    public function count($colonne = '*') {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }

    public function max($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }

    public function min($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }

    public function avg($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }

    public function sum($colonne) {
        return $this->appelFonctionDbAgregat(__FUNCTION__, $colonne);
    }

    #####################################
    # Select

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
                if (is_numeric($alias)) $alias                 = null;
                else $this->selectAliases[] = $alias;
                $this->selectSingle($colonne, $alias);
            }
        }

        if ($this->autoSelectChild) {
            $model = $this->model;
            $this->selectSingle($model::getIdentifier(false));

            $relations = $this->model->relations();
            foreach ($relations::getForeignKeys($model::belongsTo()) as $key) {
                $this->selectSingle($key);
            }
        }

        return $this;
    }

    // NOTE: TEMPORAIRE
    public function autoSelectChild($value = TRUE) {
        $this->autoSelectChild = (bool) $value;
        return $this;
    }

    public function selectExpr() {
        $colonnes = $this->normaliserSelectPlusieursColonnes(func_get_args());
        foreach ($colonnes as $alias => $colonne) {
            if (is_numeric($alias)) {
                $alias = null;
                if (stripos($colonne, ' AS ') !== false) {
                    $str = explode(' AS ', $colonne);
                    array_shift($str);
                    foreach ($str as $value) {
                        if (preg_match('/^[a-zA-Z\_\d]+/', $value, $aliasSelect))
                                $this->selectAliases[] = $aliasSelect[0];
                    }
                }
            } else {
                $this->selectAliases[] = $alias;
            }
            $this->ajoutResultatColonne($colonne, $alias);
        }
        return $this;
    }

    #####################################
    # Where

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
                    if ($args[1] === null) return $this->whereNull($args[0]);
                    else
                            return $this->ajoutWhereSimple($args[0], '=', $args[1]);
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

    public function whereNot($colonne, $valeur) {
        if (is_array($valeur)) {
            if (count($valeur)) {
                return $this->whereNotIn($colonne, $valeur);
            }
        }
        else {
            if ($valeur === null) return $this->whereNotNull($colonne);
            else return $this->whereNotEqual($colonne, $valeur);
        }
        return $this;
    }

    public function whereNotEqual($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<>', $valeur);
    }

    public function whereLike($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, 'LIKE', $valeur);
    }

    public function whereNotLike($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, 'NOT LIKE', $valeur);
    }

    public function whereGt($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '>', $valeur);
    }

    public function whereLt($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<', $valeur);
    }

    public function whereGte($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '>=', $valeur);
    }

    public function whereLte($colonne, $valeur) {
        return $this->ajoutWhereSimple($colonne, '<=', $valeur);
    }

    public function whereIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne);
            $stringIn = $this->addWhereBinds($colonne, $valeurTab);
            return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IN (' . $stringIn . ')');
        }
        return $this;
    }

    public function whereNotIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne);
            $stringIn = $this->addWhereBinds($colonne, $valeurTab);
            return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' NOT IN (' . $stringIn . ')');
        }
        return $this;
    }

    public function whereNull($colonne) {
        $colonne = $this->formatColumn($colonne);
        return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IS NULL');
    }

    public function whereNotNull($colonne) {
        $colonne = $this->formatColumn($colonne);
        return $this->ajoutWhere(ERTools::quoteIdentifiant($colonne) . ' IS NOT NULL');
    }

    #####################################
    # Having

    public function having() {
        $args = func_get_args();
        if (count($args) == 1) {
            return $this->ajoutHaving($args[0]);
        }
        elseif (count($args) > 1) {
            if (ctype_alpha($args[0]) && count($args) == 2) {
                if (is_array($args[1])) {
                    if (count($valeurTab))
                            return $this->havingIn($args[0], $args[1]);
                } else {
                    if ($args[1] === null) return $this->havingNull($args[0]);
                    else
                            return $this->ajoutHavingSimple($args[0], '=', $args[1]);
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

    public function havingNot($colonne, $valeur) {
        if (is_array($valeur)) {
            if (count($valeur)) {
                return $this->havingNotIn($colonne, $valeur);
            }
        }
        else {
            if ($valeur === null) return $this->havingNotNull($valeur);
            else return $this->havingNotEqual($colonne, $valeur);
        }
        return $this;
    }

    public function havingNotEqual($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<>', $valeur);
    }

    public function havingLike($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, 'LIKE', $valeur);
    }

    public function havingNotLike($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, 'NOT LIKE', $valeur);
    }

    public function havingGt($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '>', $valeur);
    }

    public function havingLt($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<', $valeur);
    }

    public function havingGte($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '>=', $valeur);
    }

    public function havingLte($colonne, $valeur) {
        return $this->ajoutHavingSimple($colonne, '<=', $valeur);
    }

    public function havingIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne, false);
            $stringIn = $this->addHavingBinds($colonne, $valeurTab);
            return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IN (' . $stringIn . ')');
        }
    }

    public function havingNotIn($colonne, $valeurTab) {
        if (count($valeurTab)) {
            $colonne  = $this->formatColumn($colonne, false);
            $stringIn = $this->addHavingBinds($colonne, $valeurTab);
            return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' NOT IN (' . $stringIn . ')');
        }
    }

    public function havingNull($colonne) {
        $colonne = $this->formatColumn($colonne, false);
        return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IS NULL');
    }

    public function havingNotNull($colonne) {
        $colonne = $this->formatColumn($colonne, false);
        return $this->ajoutHaving(ERTools::quoteIdentifiant($colonne) . ' IS NOT NULL');
    }

    #####################################
    # OrderBy

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

    public function orderByDesc($colonne) {
        $this->orderBy($colonne, 'DESC');
        return $this;
    }

    public function orderByAsc($colonne) {
        $this->orderBy($colonne, 'ASC');
        return $this;
    }

    public function orderByExpr($orderByExpr) {
        if ($orderByExpr) {
            $this->orderBy[] = $orderByExpr;
        }
        return $this;
    }

    public function reorderBy() {
        $this->orderBy = [];
        return call_user_func_array(array($this, 'orderBy'), func_get_args());
    }

    #####################################
    # Limit/Offset/GroupBy

    public function limit($limit) {
        if ($limit) $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    public function paginate($page, $nb) {
        $this->offset = ($page - 1) * $nb;
        $this->limit  = $nb;
        return $this;
    }

    public function groupBy() {
        $args = func_get_args();
        foreach ($args as $colonne) {
            $colonne         = $this->formatColumn($colonne, false);
            $this->groupBy[] = ERTools::quoteIdentifiant($colonne);
        }
        return $this;
    }

    #####################################
    # Query Execute

    public function first($n = 1) {
        $this->first = true;
        return $this->take($n);
    }

    public function last($n = 1) {
        $this->last = true;
        return $this->take($n);
    }

    public function take($n = 1) {
        if (!is_int($n))
                throw new ERException(get_class($this->model) . '::take() expects paramter 1 to be a string, ' . gettype($n) . ' given');
        $this->limit($n);
        $lignes = $this->run();
        if (empty($lignes)) {
            if ($n == 1) return false;
            else return [];
        }
        if ($n == 1) return $lignes[0];
        return $lignes;
    }

    public function all() {
        return $this->toArray();
    }

    public function toArray() {
        $this->runQuery();
        return $this->results;
    }

    public function find($id) {

        if (!is_array($id)) {
            return $this->findCacheFull($id);
        }

        $model = $this->model;
        return $this->whereIn($model::getIdentifier(false), $id);
    }

    #####################################
    # Junctions

    /**
     * Deprecated : alias de join
     * @param  String $table       Table à joindre
     * @param  String $contrainte  Jointure
     * @param  String $tableAlias Alias de la table jointe
     * @return This
     */
    public function joinWhere($table, array $contrainte, $tableAlias = null) {
        $this->join($table, $contrainte, $tableAlias);

        return $this;
    }

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
                else $aliasConcatTable = $arg;
            }
            else $condition = $arg;
        }
        foreach ($this->joinsIncludes($key, $alias, $aliasConcatTable, $condition) as $detail) {
            $this->leftJoin($detail['table'], [$detail['cleEtrangere'] => $detail['identifier'], $detail['condition']], $detail['alias']);
        }
        return $this;
    }

    public function joins($key, $alias = null, $aliasConcatTable = null) {
        foreach ($this->joinsIncludes($key, $alias, $aliasConcatTable) as $detail) {
            $this->join($detail['table'], [$detail['identifier'] => $detail['cleEtrangere']], $detail['alias']);
        }
        return $this;
    }

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

    #####################################
    # Private Query Maker

    private function findCacheFull($id) {

        $result    = FALSE;
        $model     = $this->model;
        $className = get_class($model);

        $isCache = $model::cacheActivation() == 'full';

        if ($isCache) {
            $result = ERCache::getInstance()->nsGet('EasyRecordCache', $className . '_' . $id . $model::cacheActivation() . $model::cacheTime());
        }

        if (!$result) {
            $this->where($model::getIdentifier(false), $id);
            $result = $this->take();

            if ($isCache && $result) {
                ERCache::getInstance()->nsSet('EasyRecordCache', $className . '_' . $id . $model::cacheActivation() . $model::cacheTime(), $result, $model::cacheTime());
            }
        }

        return $result;
    }

    private function normaliserSelectPlusieursColonnes($colonnes) {
        $return = [];
        foreach ($colonnes as $colonne) {
            if (is_array($colonne)) {
                foreach ($colonne as $key => $value) {
                    if (!is_numeric($key)) $return[$value] = $key;
                    else $return[]       = $value;
                }
            } else {
                $return[] = $colonne;
            }
        }
        return $return;
    }

    private function ajoutResultatColonne($expr, $alias = null) {
        if ($alias !== null)
                $expr .= ' AS ' . ERTools::quoteIdentifiant($alias);
        if (array_search($expr, $this->resultColonnes) === false)
                $this->resultColonnes[] = $expr;
        return $this;
    }

    private function ajoutWhere($fragment) {
        return $this->ajoutCondition('where', $fragment);
    }

    private function ajoutWhereSimple($colonne, $separateur, $valeur) {
        return $this->ajoutConditionSimple('where', $colonne, $separateur, $valeur);
    }

    private function ajoutJoinSource($joinOperateur, $table, $contrainte, $tableAlias = null) {
        $model         = $this->model;
        $joinOperateur = trim($joinOperateur . ' JOIN');
        $table         = ERTools::quoteIdentifiant($table);
        if ($tableAlias !== null) {
            $tableAlias = ERTools::quoteIdentifiant($tableAlias);
            $table .= ' ' . $tableAlias;
        }
        reset($contrainte);
        $premiereColonne = key($contrainte);
        $secondeColonne  = $contrainte[$premiereColonne];
        $condition       = '';

        if (array_key_exists(0, $contrainte) && $contrainte[0]) {
            foreach ($contrainte[0] as $conditionPremiereColonne => $conditionSecondeColonne) {

                if (is_numeric($conditionPremiereColonne)) {
                    $condition .= ' AND (' . $conditionSecondeColonne . ')';
                }
                else {
                    $type_colonne = 's';

                    if (array_key_exists($conditionPremiereColonne, $model::mapping())) {
                        $type_colonne = $model::mapping()[$conditionPremiereColonne]['type'][0];
                    }
                    $conditionPremiereColonne = $this->formatColumn($conditionPremiereColonne);
                    $condition .= ' AND ' . ERTools::quoteIdentifiant($conditionPremiereColonne) . ' = ?';

                    $arrayBinds             = $this->joinBinds;
                    $arrayBinds['types'][]  = $type_colonne;
                    $arrayBinds['values'][] = $conditionSecondeColonne;

                    $this->joinBinds = $arrayBinds;
                }
            }
        }
        if (!$this->joinFirstTable) {
            if (substr_count($secondeColonne, '.') == 1) {
                $this->joinFirstTable = explode('.', $secondeColonne)[0];
            }
            else {
                $staticTable          = $model::table();
                $tableAlias           = $this->tableAlias ? $this->tableAlias : $staticTable;
                $this->joinFirstTable = ERTools::quoteIdentifiant($tableAlias);
            }
        }
        $premiereColonne     = $this->formatColumn($premiereColonne);
        $secondeColonne      = $this->formatColumn($secondeColonne);
        $contrainte          = ERTools::quoteIdentifiant($premiereColonne) . ' = ' . ERTools::quoteIdentifiant($secondeColonne);
        $this->joinSources[] = $joinOperateur . ' ' . $table . ' ON ' . $contrainte . $condition;
        return $this;
    }

    private function addWhereBinds($colonne, $valeurTab) {
        $model    = $this->model;
        $aryIMark = [];
        $type     = 's';
        if (array_key_exists($colonne, $model::mapping()))
                $type     = $model::mapping()[$colonne]['type'][0];
        foreach ($valeurTab as $value) {
            $this->whereBinds['types'][]  = $type;
            $this->whereBinds['values'][] = $value;
            $aryIMark[]                   = '?';
        }
        return implode(', ', $aryIMark);
    }

    private function addHavingBinds($colonne, $valeurTab) {
        $model    = $this->model;
        $aryIMark = [];
        $type     = 's';
        if (array_key_exists($colonne, $model::mapping()))
                $type     = $model::mapping()[$colonne]['type'][0];
        foreach ($valeurTab as $value) {
            $this->havingBinds['types'][]  = $type;
            $this->havingBinds['values'][] = $value;
            $aryIMark[]                    = '?';
        }
        return implode(', ', $aryIMark);
    }

    private function ajoutHavingSimple($colonne, $separateur, $valeur) {
        return $this->ajoutConditionSimple('having', $colonne, $separateur, $valeur);
    }

    private function selectSingle($colonne, $alias = null) {
        if ($colonne !== '*') $colonne = $this->formatColumn($colonne);
        return $this->ajoutResultatColonne(ERTools::quoteIdentifiant($colonne), $alias);
    }

    private function appelFonctionDbAgregat($fonctionSql, $colonne) {
        $fonctionSql = strtoupper($fonctionSql);
        if ('*' != $colonne) $colonne     = ERTools::quoteIdentifiant($colonne);
        $this->selectExpr([$fonctionSql . '(' . $colonne . ')' => $fonctionSql]);
        return $this;
    }

    private function ajoutHaving($fragment) {
        return $this->ajoutCondition('having', $fragment);
    }

    private function ajoutConditionSimple($type, $colonne, $separateur, $valeur) {
        $model                  = $this->model;
        $colonneOriginale       = $colonne;
        $colonne                = $this->formatColumn($colonne, ($type != 'having'));
        $type_colonne           = 's';
        if (array_key_exists($colonneOriginale, $model::mapping()))
                $type_colonne           = $model::mapping()[$colonneOriginale]['type'][0];
        $bindType               = $type . 'Binds';
        $arrayBinds             = $this->$bindType;
        $arrayBinds['types'][]  = $type_colonne;
        $arrayBinds['values'][] = $valeur;
        $this->$bindType        = $arrayBinds;
        return $this->ajoutCondition($type, ERTools::quoteIdentifiant($colonne) . ' ' . $separateur . ' ?');
    }

    private function ajoutCondition($type, $fragment) {
        $conditionsType = $type . 'Conditions';
        array_push($this->$conditionsType, '(' . $fragment . ')');
        return $this;
    }

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
        throw new ERException('EasyRecord::joinsIncludes() parameter 1 is not a known relationship', 1);
    }

    private function joinsIncludesHasManyThrough($key, $alias, $aliasConcatTable, $condition, $infosArray, $hasMany, $tableAlias) {
        $model     = $this->model;
        $through   = $infosArray['through'];
        if ($through[strlen($through) - 1] == 's')
                $through   = substr($through, 0, strlen($through) - 1);
        $belongsTo = get_class($model);
        if (array_key_exists($belongsTo, $through::belongsTo())) {
            $infosArray            = $through::belongsTo()[$belongsTo];
            if (array_key_exists('foreign_key', $infosArray))
                    $cleEtrangereBelongsTo = $infosArray['foreign_key'];
            if (array_key_exists('class_name', $infosArray))
                    $belongsTo             = $this->model->getNamespace() . $infosArray['class_name'];
            if (!isset($cleEtrangereBelongsTo) && array_key_exists('inverse_of', $infosArray))
                    $cleEtrangereBelongsTo = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
        }
        if (!isset($cleEtrangereBelongsTo))
                $cleEtrangereBelongsTo = $model::getIdentifier(false);
        if (array_key_exists($hasMany, $through::belongsTo())) {
            $infosArray          = $through::belongsTo()[$hasMany];
            if (array_key_exists('foreign_key', $infosArray))
                    $cleEtrangereHasMany = $infosArray['foreign_key'];
            if (array_key_exists('class_name', $infosArray))
                    $hasMany             = $this->model->getNamespace() . $infosArray['class_name'];
            if (!isset($cleEtrangereHasMany) && array_key_exists('inverse_of', $infosArray))
                    $cleEtrangereHasMany = $hasMany::hasMany()[$infosArray['inverse_of']]['foreign_key'];
        }
        if (!isset($cleEtrangereHasMany))
                $cleEtrangereHasMany = $hasMany::getIdentifier(false);
        if (!$alias) $alias               = $hasMany::table();
        if (!$aliasConcatTable) $aliasConcatTable    = $through::table();
        $throughDatabase     = $through::database();
        return [['table' => $throughDatabase . '.' . $through::table(), 'cleEtrangere' => $aliasConcatTable . '.' . $cleEtrangereBelongsTo, 'identifier' => $tableAlias . '.' . $model::getIdentifier(false), 'alias' => $aliasConcatTable, 'condition' => []],
            ['table' => $throughDatabase . '.' . $hasMany::table(), 'cleEtrangere' => $aliasConcatTable . '.' . $cleEtrangereHasMany, 'identifier' => $alias . '.' . $hasMany::getIdentifier(false), 'alias' => $alias, 'condition' => $condition]
        ];
    }

    private function joinsIncludesHasMany($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasMany     = $this->model->getNamespace() . $string;
        if ($hasMany[strlen($hasMany) - 1] == 's')
                $hasMany     = substr($hasMany, 0, strlen($hasMany) - 1);
        if (array_key_exists($string, $model::hasMany())) {
            $infosArray   = $model::hasMany()[$string];
            if (array_key_exists('foreign_key', $infosArray))
                    $cleEtrangere = $infosArray['foreign_key'];
            if (array_key_exists('class_name', $infosArray))
                    $hasMany      = $this->model->getNamespace() . $infosArray['class_name'];
            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray))
                    $cleEtrangere = $hasMany::belongsTo()[$infosArray['inverse_of']]['foreign_key'];
            if (array_key_exists('through', $infosArray)) {
                return $this->joinsIncludesHasManyThrough($key, $alias, $aliasConcatTable, $condition, $infosArray, $hasMany, $tableAlias);
            }
        }
        if (!isset($cleEtrangere)) $cleEtrangere = $model::getIdentifier(false);
        if (!$alias) $alias        = $hasMany::table();
        return [['table' => $hasMany::database() . '.' . $hasMany::table(), 'cleEtrangere' => $alias . '.' . $cleEtrangere, 'alias' => $alias, 'identifier' => $tableAlias . '.' . $model::getIdentifier(false), 'condition' => $condition]];
    }

    private function joinsIncludesHasOne($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasOne      = $this->model->getNamespace() . $string;
        if (array_key_exists($string, $model::hasOne())) {
            $infosArray   = $model::hasOne()[$string];
            if (array_key_exists('foreign_key', $infosArray))
                    $cleEtrangere = $infosArray['foreign_key'];
            if (array_key_exists('class_name', $infosArray))
                    $hasOne       = $this->model->getNamespace() . $infosArray['class_name'];
            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray))
                    $cleEtrangere = $hasOne::belongsTo()[$infosArray['inverse_of']]['foreign_key'];
        }
        if (!isset($cleEtrangere)) $cleEtrangere = $model::getIdentifier(false);
        if (!$alias) $alias        = $hasOne::table();
        return [['table' => $hasOne::database() . '.' . $hasOne::table(), 'cleEtrangere' => $alias . '.' . $cleEtrangere, 'alias' => $alias, 'identifier' => $tableAlias . '.' . $model::getIdentifier(false), 'condition' => $condition]];
    }

    private function joinsIncludesBelongsTo($key, $alias, $aliasConcatTable, $condition) {
        $model       = $this->model;
        $string      = ucfirst($key);
        $staticTable = $model::table();
        $tableAlias  = $this->tableAlias ? $this->tableAlias : $staticTable;
        $belongsTo   = $this->model->getNamespace() . $string;
        if (array_key_exists($string, $model::belongsTo())) {
            $infosArray   = $model::belongsTo()[$string];
            if (array_key_exists('foreign_key', $infosArray))
                    $cleEtrangere = $infosArray['foreign_key'];
            if (array_key_exists('class_name', $infosArray))
                    $belongsTo    = $this->model->getNamespace() . $infosArray['class_name'];
            if (!isset($cleEtrangere) && array_key_exists('inverse_of', $infosArray))
                    $cleEtrangere = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
        }
        if (!isset($cleEtrangere))
                $cleEtrangere = $belongsTo::getIdentifier(false);
        if (!$alias) $alias        = $belongsTo::table();
        return [['table' => $belongsTo::database() . '.' . $belongsTo::table(), 'cleEtrangere' => $alias . '.' . $belongsTo::getIdentifier(false), 'alias' => $alias, 'identifier' => $tableAlias . '.' . $cleEtrangere, 'condition' => $condition]];
    }

    private function joinsIncludesHasAndBelongsToMany($key, $alias, $aliasConcatTable, $condition) {
        $model               = $this->model;
        $string              = ucfirst($key);
        $staticTable         = $model::table();
        $tableAlias          = $this->tableAlias ? $this->tableAlias : $staticTable;
        $hasAndBelongsToMany = $this->model->getNamespace() . $string;
        if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany) - 1] == 's')
                $hasAndBelongsToMany = substr($hasAndBelongsToMany, 0, strlen($hasAndBelongsToMany) - 1);
        $database            = $model::database();
        if (array_key_exists($string, $model::hasAndBelongsToMany())) {
            $infosArray              = $model::hasAndBelongsToMany()[$string];
            if (array_key_exists('class_name', $infosArray))
                    $hasAndBelongsToMany     = $this->model->getNamespace() . $infosArray['class_name'];
            if (array_key_exists('inverse_of', $infosArray))
                    $infosArray              = $hasAndBelongsToMany::hasAndBelongsToMany()[$infosArray['inverse_of']];
            if (array_key_exists('association_foreign_key', $infosArray))
                    $associationCleEtrangere = $infosArray['association_foreign_key'];
            if (array_key_exists('foreign_key', $infosArray))
                    $concatCleEtrangere      = $infosArray['foreign_key'];
            if (array_key_exists('table', $infosArray))
                    $concatTable             = $infosArray['table'];
            if (array_key_exists('database', $infosArray))
                    $database                = $infosArray['database'];
        }
        if (!isset($associationCleEtrangere))
                $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier(false);
        if (!isset($concatCleEtrangere))
                $concatCleEtrangere      = $model::getIdentifier(false);
        $habtmTable              = $hasAndBelongsToMany::table();
        $objetTable              = $hasAndBelongsToMany::database() . '.' . $habtmTable;
        if (!isset($concatTable)) {
            if ($habtmTable < $staticTable)
                    $concatTable = $habtmTable . '_' . $staticTable;
            else $concatTable = $staticTable . '_' . $habtmTable;
        }
        $concatTableDb    = $database . '.' . $concatTable;
        if (!$alias) $alias            = $habtmTable;
        if (!$aliasConcatTable) $aliasConcatTable = $concatTable;
        return [['table' => $concatTableDb, 'cleEtrangere' => $aliasConcatTable . '.' . $concatCleEtrangere, 'identifier' => $tableAlias . '.' . $model::getIdentifier(false), 'alias' => $aliasConcatTable, 'condition' => $condition],
            ['table' => $objetTable, 'cleEtrangere' => $aliasConcatTable . '.' . $associationCleEtrangere, 'identifier' => $alias . '.' . $hasAndBelongsToMany::getIdentifier(false), 'alias' => $alias, 'condition' => $condition]
        ];
    }

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

    private function formatColumn($colonne, $check = true) {
        $model = $this->model;
        if (($check || !in_array($colonne, $this->selectAliases)) && strpos($colonne, '.') === false) {
            if ($this->tableAlias !== null)
                    $colonne = $this->tableAlias . '.' . $colonne;
            else $colonne = $model::table() . '.' . $colonne;
        }
        return $colonne;
    }

    #####################################
    # Private Query Builder

    private function buildSelect() {
        $this->bindParam = new ERBindParam();
        if ($this->rawQuery) return $this->rawQuery;
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

    private function buildSelectStart() {
        $model = $this->model;
        if (!$this->resultColonnes) {
            $tableAlias = $this->tableAlias ? $this->tableAlias : $model::table();
            $this->selectAll($tableAlias);
        }
        $resultColonnes = implode(', ', $this->resultColonnes);

        if ($this->distinct) $resultColonnes = 'DISTINCT ' . $resultColonnes;

        $this->sqlQuery = 'SELECT ' . $resultColonnes . ' FROM ';
        return $this;
    }

    private function buildFrom() {

        // A from is given
        if ($this->fromExpr) {
            $this->sqlQuery .= $this->fromExpr;
        }
        // Default table
        else {
            $model = $this->model;

            $this->sqlQuery .= ERTools::quoteIdentifiant($model::database()) . '.' . ERTools::quoteIdentifiant($model::table());
            if ($this->tableAlias !== NULL) {
                $this->sqlQuery .= ' ' . ERTools::quoteIdentifiant($this->tableAlias);
            }
        }

        return $this;
    }

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

    private function buildWhere() {
        if (count($this->whereConditions)) {
            $whereConditions = implode(' AND ', $this->whereConditions);
            if (count($this->joinWhereTables))
                    $this->sqlQuery .= ' AND ' . $whereConditions;
            else $this->sqlQuery .= ' WHERE ' . $whereConditions;
            foreach ($this->whereBinds['values'] as $index => $value) {
                $this->bindParam->add($this->whereBinds['types'][$index], $value);
            }
        }
        return $this;
    }

    private function buildGroupBy() {
        if (count($this->groupBy))
                $this->sqlQuery .= ' GROUP BY ' . implode(', ', $this->groupBy);
        return $this;
    }

    private function buildHaving() {
        if (count($this->havingConditions)) {
            $this->sqlQuery .= ' HAVING ' . implode(' AND ', $this->havingConditions);
            foreach ($this->havingBinds['values'] as $index => $value) {
                $this->bindParam->add($this->havingBinds['types'][$index], $value);
            }
        }
        return $this;
    }

    private function buildOrderBy() {
        if (count($this->orderBy))
                $this->sqlQuery .= ' ORDER BY ' . implode(', ', $this->orderBy);
        return $this;
    }

    private function buildLimit() {
        if ($this->limit !== null) $this->sqlQuery .= ' LIMIT ' . $this->limit;
        return $this;
    }

    private function buildOffset() {
        if ($this->offset !== null)
                $this->sqlQuery .= ' OFFSET ' . $this->offset;
        return $this;
    }

    #####################################
    # Magic

    public function __get($key) {
        return $this->model->relations()->getRelations($key, $this->model, $this->tableAlias);
    }

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

    public function delete() {
        if ($this->belongsToDetails) {
            $modelName = $this->belongsToDetails['model'];
            $referer   = $modelName::find($this->belongsToDetails['referer']);
            return $referer->deleteHasMany(str_replace('Agendaweb\App\Models\\', '', get_class($this->model)) . 's');
        }
        return false;
    }

    private function run() {
        $model = $this->model;
        if (!$this->unscoped) {
            foreach ($this->scopesToExecute as $scope) {
                call_user_func_array($model::getScopes()[$scope['name']], array_merge([$this], $scope['args']));
            }
            $this->scopesToExecute = [];
        }
        if (empty($this->orderBy)) {
            if ($this->first) $this->orderBy($model::getIdentifier(false));
            elseif ($this->last)
                    $this->orderByDesc($model::getIdentifier(false));
        }
        $this->sqlQuery = $this->buildSelect();

        $listeObjects = $this->getObjects();

        return $listeObjects;
    }

    private function getObjects() {
        $model        = $this->model;
        $listeObjects = [];
        $result       = ERTools::execute($this->sqlQuery, $this->bindParam);

        while ($result->next()) {
            if ($this->lightMode == 0)
                    $listeObjects[] = self::instantiate($result->row(), get_class($this->model), $this->model->getReadOnly());
            elseif ($this->lightMode == 2)
                    $listeObjects[] = array_change_key_case($result->row());
            else $listeObjects[] = $result->get($model::getIdentifier(false));
        }
        return $listeObjects;
    }

    private static function instantiate($record, $class, $readOnly) {
        $object = new $class(true);
        if ($readOnly) $object->readOnly();
        foreach ($record as $attribut => $value) {
            $object->setAttribute($attribut, $value);
        }
        $object->notNew();
        return $object;
    }

    private function runQuery() {
        if (!$this->queryExecuted) {
            $this->results       = $this->run();
            $this->queryExecuted = true;
        }
    }

    private function addRemoveRelation($name, $arguments, $outputArray) {
        $model  = $this->model;
        $string = $outputArray[1];
        if (in_array($string, $model::hasAndBelongsToMany()) || array_key_exists($string, $model::hasAndBelongsToMany())) {
            if (count($arguments) == 1) {
                $hasAndBelongsToMany = $this->model->getNamespace() . $string;
                if ($hasAndBelongsToMany[strlen($hasAndBelongsToMany) - 1] == 's')
                        $hasAndBelongsToMany = substr($hasAndBelongsToMany, 0, strlen($hasAndBelongsToMany) - 1);
                $database            = $model::database();
                if (array_key_exists($string, $model::hasAndBelongsToMany())) {
                    $infosArray              = $model::hasAndBelongsToMany()[$string];
                    if (array_key_exists('class_name', $infosArray))
                            $hasAndBelongsToMany     = $this->model->getNamespace() . $infosArray['class_name'];
                    if (array_key_exists('inverse_of', $infosArray))
                            $infosArray              = $hasAndBelongsToMany::hasAndBelongsToMany()[$infosArray['inverse_of']];
                    if (array_key_exists('association_foreign_key', $infosArray))
                            $associationCleEtrangere = $infosArray['association_foreign_key'];
                    if (array_key_exists('foreign_key', $infosArray))
                            $concatCleEtrangere      = $infosArray['foreign_key'];
                    if (array_key_exists('table', $infosArray))
                            $concatTable             = $infosArray['table'];
                    if (array_key_exists('database', $infosArray))
                            $database                = $infosArray['database'];
                }
                if (!isset($associationCleEtrangere))
                        $associationCleEtrangere = $hasAndBelongsToMany::getIdentifier(false);
                if (!isset($concatCleEtrangere))
                        $concatCleEtrangere      = $model::getIdentifier(false);
                $staticTable             = $model::table();
                $habtmTable              = $hasAndBelongsToMany::table();
                $objetTable              = $hasAndBelongsToMany::database() . '.' . $habtmTable;
                if (!isset($concatTable)) {
                    if ($habtmTable < $staticTable)
                            $concatTable = $habtmTable . '_' . $staticTable;
                    else $concatTable = $staticTable . '_' . $habtmTable;
                }
                $concatTable  = $database . '.' . $concatTable;
                if (is_object($arguments[0]))
                        $arguments[0] = $arguments[0]->getIdentifierValue();

                $details = ['concatTable' => $concatTable, 'objectId' => $associationCleEtrangere, 'objectValue' => $arguments[0], 'thisId' => $concatCleEtrangere];
                if (preg_match('/add([\w]*)/', $name, $outputArray)) {
                    if (!array_key_exists($concatTable, $model->relations()->hasAndBelongsToManyToAdd))
                            $model->relations()->hasAndBelongsToManyToAdd[$concatTable]                = [];
                    $model->relations()->hasAndBelongsToManyToAdd[$concatTable][$arguments[0]] = $details;
                } else {
                    if (!array_key_exists($concatTable, $model->relations()->hasAndBelongsToManyToRemove))
                            $model->relations()->hasAndBelongsToManyToRemove[$concatTable]                = [];
                    $model->relations()->hasAndBelongsToManyToRemove[$concatTable][$arguments[0]] = $details;
                }
                return true;
            } else {
                throw new ERException('EasyRecord::add and EasyRecord::remove on a hasAndBelongsToMany relationship expects exactly 1 parameter, 0 given', 1);
            }
        }
        elseif (array_key_exists($string, $model::hasMany()) && array_key_exists('through', $model::hasMany()[$string])) {
            if (count($arguments) >= 1) {
                $hasMany = $this->model->getNamespace() . $string;
                if ($hasMany[strlen($hasMany) - 1] == 's')
                        $hasMany = substr($hasMany, 0, strlen($hasMany) - 1);
                $through = $model::hasMany()[$string]['through'];
                if ($through[strlen($through) - 1] == 's')
                        $through = substr($through, 0, strlen($through) - 1);

                if (is_object($arguments[0]))
                        $arguments[0] = $arguments[0]->getIdentifierValue();
                if (preg_match('/add([\w]*)/', $name, $outputArray)) {
                    if (!array_key_exists($through, $model->relations()->hasManyToAdd))
                            $model->relations()->hasManyToAdd[$through]                = [];
                    if (!array_key_exists(1, $arguments)) $arguments[1]                                              = [];
                    $model->relations()->hasManyToAdd[$through][$arguments[0]] = ['objectClass' => $through, 'relName' => $hasMany, 'relValue' => $arguments[0], 'args' => $arguments[1]];
                } else {
                    if (!array_key_exists($through, $model->relations()->hasManyToRemove))
                            $model->relations()->hasManyToRemove[$through] = [];

                    $belongsTo = get_class($model);
                    if (array_key_exists($belongsTo, $through::belongsTo())) {
                        $infosArray            = $through::belongsTo()[$belongsTo];
                        if (array_key_exists('foreign_key', $infosArray))
                                $cleEtrangereBelongsTo = $infosArray['foreign_key'];
                        if (array_key_exists('class_name', $infosArray))
                                $belongsTo             = $this->model->getNamespace() . $infosArray['class_name'];
                        if (!isset($cleEtrangereBelongsTo) && array_key_exists('inverse_of', $infosArray))
                                $cleEtrangereBelongsTo = $belongsTo::hasMany()[$infosArray['inverse_of']]['foreign_key'];
                    }
                    if (!isset($cleEtrangereBelongsTo))
                            $cleEtrangereBelongsTo = $model::getIdentifier(false);

                    if (array_key_exists($hasMany, $through::belongsTo())) {
                        $infosArray          = $through::belongsTo()[$hasMany];
                        if (array_key_exists('foreign_key', $infosArray))
                                $cleEtrangereHasMany = $infosArray['foreign_key'];
                        if (array_key_exists('class_name', $infosArray))
                                $hasMany             = $this->model->getNamespace() . $infosArray['class_name'];
                        if (!isset($cleEtrangereHasMany) && array_key_exists('inverse_of', $infosArray))
                                $cleEtrangereHasMany = $hasMany::hasMany()[$infosArray['inverse_of']]['foreign_key'];
                    }
                    if (!isset($cleEtrangereHasMany))
                            $cleEtrangereHasMany                                          = $hasMany::getIdentifier(false);
                    $model->relations()->hasManyToRemove[$through][$arguments[0]] = ['objectClass' => $through, 'hasManyKey' => $cleEtrangereHasMany, 'belongsToKey' => $cleEtrangereBelongsTo, 'hasManyValue' => $arguments[0]];
                }
                return true;
            } else {
                throw new ERException('EasyRecord::add and EasyRecord::remove on a hasMany relationship expects at least 1 parameter, 0 given', 1);
            }
        }
    }

}

/* End of file */