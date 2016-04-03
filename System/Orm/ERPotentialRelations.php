<?php

namespace System\Orm;



/**
 * ERRelations Class
 *
 * @author anaeria
 */

class ERPotentialRelations
{
	protected $data = [];
	protected $potential_relations = [];



    /**
     * Définie les données de la relation
     *
     * @param array
     */

	public function setData($data) {
		$this->data = $data;
	}


	// -------------------------------------------------------------------------

	/**
     * Définie une donnée de la relation
     *
     * @param string
     * @param mixed
     */

	public function setAttribute($name, $value) {
		$this->data[$name] = $value;
	}


	// -------------------------------------------------------------------------

	/**
     * Définie une relation potentielle
     *
     * @param string
     * @param object
     */

	public function addPorentialRelation($name, $potentialRelation) {
		$this->potential_relations[$name] = $potentialRelation;
	}


	// -------------------------------------------------------------------------

	/**
     * Détruit une relation potentielle
     *
     * @param string
     */

    public function deletePotentialRelation($name) {
        if(isset($this->potential_relations[$name])) {
            unset($this->potential_relations[$name]);
        }
    }


    // -------------------------------------------------------------------------

	/**
     * Initialise une relation potentielle
     *
     * @param string
     */

    public function preparePotentialRelation($name) {
        $this->potential_relations[$name] = new ERPotentialRelations();
    }


    // -------------------------------------------------------------------------

	/**
     * Retourne une relation potentielle
     *
     * @param string
     * @return object
     */

    public function getPotentialRelation($name) {
    	if(isset($this->potential_relations[$name])) {
    		return $this->potential_relations[$name];
    	} else {
    		throw new ERException('Potential relation ' . $name .' not found');
    	}
    }


    // -------------------------------------------------------------------------

	/**
     * Instancie la relation potentielle avec le model fourni
     *
     * @param string
     * @return object
     */

    public function instanciateAs($className) {
        $object = new $className(true);

        foreach ($this->data as $attribut => $value) {
            $object->setAttribute($attribut, $value);
        }

        foreach ($this->potential_relations as $attribut => $value) {
            $object->setPotentialRelation($attribut, $value);
        }
        $object->notNew();

        return $object;
    }
}

/* End of file */