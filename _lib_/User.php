<?php

class User
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var DateTime
	 */
	private $createdAt;

	/**
	 * @var DateTime?
	 */
	private $lastVisitAt;

	/**
	 * @var bool
	 */
	private $isSuper;

	/**
	 * @var array<string,bool>
	 */
	private $privilages = array();

	/**
	 * @var array<string,bool>
	 */
	private $allowedMachines = array();

	/**
	 * @var array<int,bool>
	 */
	private $allowedFactories = array();

	/**
	 * @param  int $id
	 * @param  string $name
	 * @param  string $email
	 * @param  string $createdAt
	 * @param  string? $lastVisitAt
	 * @param  array<string,bool> $privilages
	 */
	public function __construct($id, $name, $email, $createdAt, $lastVisitAt = null, $isSuper = false)
	{
		$this->id = (int)$id;
		$this->name = $name;
		$this->email = $email;
		$this->createdAt = new DateTime($createdAt);
		$this->lastVisitAt = $lastVisitAt ? new DateTime($lastVisitAt) : null;
		$this->isSuper = $isSuper;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}
	
	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getCreatedAt()
	{
		return $this->createdAt->format('Y-m-d H:i');
	}

	/**
	 * @return string
	 */
	public function getLastVisitAt()
	{
		return $this->lastVisitAt ? $this->lastVisitAt->format('Y-m-d H:i') : '';
	}

	/**
	 * @param  array<string,bool> $privilages
	 */
	public function setPrivilages(array $privilages)
	{
		$this->privilages = $privilages;
	}

	/**
	 * @param  array<int,bool> $allowedFactories
	 */
	public function setAllowedFactories(array $allowedFactories)
	{
		$this->allowedFactories = $allowedFactories;
	}

	/**
	 * @return array<int>
	 */
	public function getAllowedFactoryIds()
	{
		$ids = array(0);

		foreach ($this->allowedFactories as $id => $allowed)
		{
			if ($allowed)
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * @param  array<string,bool> $allowedMachines
	 */
	public function setAllowedMachines(array $allowedMachines)
	{
		$this->allowedMachines = $allowedMachines;
	}

	/**
	 * @return array<int>
	 */
	public function getAllowedMachineIds()
	{
		$ids = array(0);

		foreach ($this->allowedMachines as $id => $allowed)
		{
			if ($allowed)
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * @return bool
	 */
	public function isSuper()
	{
		return $this->isSuper;
	}

	/**
	 * @param  string $privilage
	 * @return bool
	 */
	public function isAllowedTo($privilage)
	{
		if ($this->isSuper || ($privilage === '*'))
		{
			return true;
		}

		if (strpos($privilage, '*') !== false)
		{
			$pattern = '#^' . str_replace('*', '.*', $privilage) . '$#';

			foreach ($this->privilages as $privilage => $allowed)
			{
				if (preg_match($pattern, $privilage) && $allowed)
				{
					return true;
				}
			}

			return false;
		}

		return isset($this->privilages[$privilage]);
	}

	/**
	 * @param  int $id
	 * @return bool
	 */
	public function hasAccessToFactory($id)
	{
		return $this->isSuper || isset($this->allowedFactories[$id]);
	}

	/**
	 * @param  int $id
	 * @return bool
	 */
	public function hasAccessToMachine($id)
	{
		return $this->isSuper || isset($this->allowedMachines[$id]);
	}

	public function getAllowedFactories()
	{
		return $this->allowedFactories;
	}

	public function getAllowedMachines()
	{
		return $this->allowedMachines;
	}
}
