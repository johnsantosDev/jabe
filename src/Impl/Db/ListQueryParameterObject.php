<?php

namespace Jabe\Impl\Db;

class ListQueryParameterObject
{
    protected $authCheck;

    protected $tenantCheck;
    protected $orderingProperties = [];

    protected $maxResults;
    protected $firstResult;
    protected $parameter;
    protected $databaseType;

    public function __construct($parameter = null, ?int $firstResult = null, ?int $maxResults = null)
    {
        $this->authCheck = new AuthorizationCheck();
        $this->tenantCheck = new TenantCheck();
        $this->parameter = $parameter;
        $this->firstResult = $firstResult ?? 0;
        $this->maxResults = $maxResults ?? PHP_INT_MAX;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    public function getFirstRow(): int
    {
        return $this->firstResult + 1;
    }

    public function getLastRow(): int
    {
        if ($this->maxResults == PHP_INT_MAX) {
            return $this->maxResults;
        }
        return $this->firstResult + $this->maxResults + 1;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function setFirstResult(int $firstResult): void
    {
        $this->firstResult = $firstResult;
    }

    public function setMaxResults(int $maxResults): void
    {
        $this->maxResults = $maxResults;
    }

    public function setParameter($parameter): void
    {
        $this->parameter = $parameter;
    }

    public function setDatabaseType(?string $databaseType): void
    {
        $this->databaseType = $databaseType;
    }

    public function getDatabaseType(): ?string
    {
        return $this->databaseType;
    }

    public function getAuthCheck(): AuthorizationCheck
    {
        return $this->authCheck;
    }

    public function setAuthCheck(AuthorizationCheck $authCheck): void
    {
        $this->authCheck = $authCheck;
    }

    public function getTenantCheck(): TenantCheck
    {
        return $this->tenantCheck;
    }

    public function setTenantCheck(TenantCheck $tenantCheck): void
    {
        $this->tenantCheck = $tenantCheck;
    }

    public function &getOrderingProperties(): array
    {
        return $this->orderingProperties;
    }

    public function setOrderingProperties(array $orderingProperties): void
    {
        $this->orderingProperties = $orderingProperties;
    }

    public function __serialize(): array
    {
        $orderingProperties = [];
        foreach ($this->orderingProperties as $prop) {
            $orderingProperties[] = serialize($prop);
        }
        return [
            'parameter' => $this->parameter,
            'firstResult' => $this->firstResult,
            'maxResults' => $this->maxResults,
            'authCheck' => serialize($this->authCheck),
            'tenantCheck' => serialize($this->tenantCheck),
            'orderingProperties' => $orderingProperties
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->parameter = $data['parameter'];
        $this->firstResult = $data['firstResult'];
        $this->maxResults = $data['maxResults'];
        $this->authCheck = unserialize($data['authCheck']);
        $this->tenantCheck = unserialize($data['tenantCheck']);
        $props = [];
        foreach ($data['orderingProperties'] as $prop) {
            $props[] = unserialize($prop);
        }
        $this->orderingProperties = $props;
    }
}
