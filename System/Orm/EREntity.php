<?php

namespace System\Orm;



/**
 * ERRelations Class
 *
 * @author anaeria
 */

class EREntity
{
	protected $data = [];
	protected $potential_relations = [];


    /**
     * Gère l'accès aux données
     *
     * @return mixed
     */

	public function __get($key) {
		if(isset($this->data[$key])) {
			return $this->data[$key];
		} elseif(isset($this->potential_relations[$key])) {
			return $this->potential_relations[$key];
		}
		return null;
	}


	// -------------------------------------------------------------------------

    /**
     * Défini une relation potentielle
     *
     * @param string
     * @param array
     */

	public function setPotentialRelation($relation, $data) {
		$this->potential_relations[$name] = $data;
	}


	// -------------------------------------------------------------------------

    /**
     * Défini une donnée
     *
     * @param string
     * @param mixed
     */

	public function setAttribute($name, $value) {
		$this->data[$name] = $value;
	}


	// -------------------------------------------------------------------------

    /**
     * Gère la capacité de lecture seule
     */

	public function readOnly() {}


	// -------------------------------------------------------------------------

    /**
     * Détermine si l'entitée est nouvelle ou non
     */

	public function notNew() {}
}