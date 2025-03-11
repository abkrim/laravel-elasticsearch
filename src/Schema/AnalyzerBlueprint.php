<?php

declare(strict_types=1);

namespace PDPhilip\Elasticsearch\Schema;

use Illuminate\Support\Fluent;
use PDPhilip\Elasticsearch\Connection;

class AnalyzerBlueprint
{
    /**
     * The Connection object for this blueprint.
     */
    protected Connection $connection;

    protected string $index = '';

    protected array $parameters = [];

    public function __construct($index)
    {
        $this->index = $index;
    }

    // ----------------------------------------------------------------------
    // Index blueprints
    // ----------------------------------------------------------------------

    public function analyzer($name): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('analyzer', $name);
    }

    protected function addProperty($config, $name, array $parameters = [])
    {
        return $this->addPropertyDefinition(new Definitions\AnalyzerPropertyDefinition(
            array_merge(compact('config', 'name'), $parameters)
        ));
    }

    protected function addPropertyDefinition($definition)
    {
        $this->parameters['analysis'][] = $definition;

        return $definition;
    }

    public function tokenizer($type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('tokenizer', $type);
    }

    // ----------------------------------------------------------------------
    // Definitions
    // ----------------------------------------------------------------------

    public function charFilter($type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('char_filter', $type);
    }

    public function filter($type): Definitions\AnalyzerPropertyDefinition
    {
        return $this->addProperty('filter', $type);
    }

    // ----------------------------------------------------------------------
    // Builders
    // ----------------------------------------------------------------------

    public function buildIndexAnalyzerSettings(Connection $connection): bool
    {
        $connection->setIndex($this->index);
        if ($this->parameters) {
            $this->_formatParams();
            $connection->indexAnalyzerSettings($this->parameters);
        }

        return false;
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------
    private function _formatParams(): void
    {
        if ($this->parameters) {
            if (! empty($this->parameters['analysis'])) {
                $properties = [];
                foreach ($this->parameters['analysis'] as $property) {
                    if ($property instanceof Fluent) {
                        $properties[] = $property->toArray();
                    } else {
                        $properties[] = $property;
                    }
                }
                $this->parameters['analysis'] = $properties;
            }
        }
    }
}
