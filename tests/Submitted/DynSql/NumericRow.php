<?php

namespace Tests\Submitted\DynSql;

class NumericRow
{
    private int $id;
    private int $tinynumber;
    private int $smallnumber;
    private int $longinteger;
    private int $biginteger;
    private float $numericnumber;
    private float $decimalnumber;
    private float $realnumber;
    private float $floatnumber;
    private float $doublenumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTinynumber(): ?int
    {
        return $this->tinynumber;
    }

    public function setTinynumber(int $tinynumber): void
    {
        $this->tinynumber = $tinynumber;
    }

    public function getSmallnumber(): int
    {
        return $this->smallnumber;
    }

    public function setSmallnumber(int $smallnumber): void
    {
        $this->smallnumber = $smallnumber;
    }

    public function getLonginteger(): int
    {
        return $this->longinteger;
    }

    public function setLonginteger(int $longinteger): void
    {
        $this->longinteger = $longinteger;
    }

    public function getBiginteger(): ?int
    {
        return $this->biginteger;
    }

    public function setBiginteger(int $biginteger): void
    {
        $this->biginteger = $biginteger;
    }

    public function getNumericnumber(): ?float
    {
        return $this->numericnumber;
    }

    public function setNumericnumber(float $numericnumber): void
    {
        $this->numericnumber = $numericnumber;
    }

    public function getDecimalnumber(): ?float
    {
        return $this->decimalnumber;
    }

    public function setDecimalnumber(float $decimalnumber): void
    {
        $this->decimalnumber = $decimalnumber;
    }

    public function getRealnumber(): ?float
    {
        return $this->realnumber;
    }

    public function setRealnumber(float $realnumber): void
    {
        $this->realnumber = $realnumber;
    }

    public function getFloatnumber(): ?float
    {
        return $this->floatnumber;
    }

    public function setFloatnumber(float $floatnumber): void
    {
        $this->floatnumber = $floatnumber;
    }

    public function getDoublenumber(): float
    {
        return $this->doublenumber;
    }

    public function setDoublenumber(float $doublenumber): void
    {
        $this->doublenumber = $doublenumber;
    }
}
