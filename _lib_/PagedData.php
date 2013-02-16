<?php

class PagedData implements IteratorAggregate
{
	private $page = 1;

	private $perPage = 10;

	private $pageCount = 0;

	private $items = array();

	public function __construct($page, $perPage = 10)
	{
		$this->page = $page < 1 ? 1 : (int)$page;
		$this->perPage = (int)$perPage;
	}

	public function isEmpty()
	{
		return empty($this->items);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	public function getPage()
	{
		return $this->page;
	}

	public function getOffset()
	{
		return ($this->page - 1) * $this->perPage;
	}

	public function getPerPage()
	{
		return $this->perPage;
	}

	public function getPageCount()
	{
		return $this->pageCount;
	}

	public function fill($totalItems, $items)
	{
		$this->pageCount = ceil($totalItems / $this->perPage);
		$this->items = $items;
	}

	public function isLinkToFirstPageAvailable()
	{
		return $this->page > 2;
	}

	public function isLinkToPreviousPageAvailable()
	{
		return $this->page > 1;
	}

	public function isLinkToNextPageAvailable()
	{
		return $this->page < $this->pageCount;
	}

	public function isLinkToLastPageAvailable()
	{
		return $this->page < ($this->pageCount - 1);
	}

	public function render($href, $param = 'page')
	{
		if ($this->getPageCount() < 2)
		{
			return '';
		}

		$result = 'Strony (<a class="page-total" title="Skocz do strony" href="' . $href . '&amp;' . $param . '=${page}">' . $this->getPageCount() . '</a>): ';

		$result .= $this->isLinkToFirstPageAvailable()
			? '<a class="page-first" href="' . $href . '&amp;' . $param . '=1">&laquo; Pierwsza</a> '
			#: '<span class="page-first">&laquo; First</span> ';
			: '';

		$result .= $this->isLinkToPreviousPageAvailable()
			? '<a class="page-prev" href="' . $href . '&amp;' . $param . '=' . ($this->getPage() - 1) . '">&lsaquo; Poprzednia</a> '
			#: '<span class="page-prev">&lsaquo; Previous</span> ';
			: '';

    $pageNumbers = 3;

		$page = $this->getPage();
		$last = $page + $pageNumbers;
		$cut = true;

		if (($page - $pageNumbers) < 1)
		{
			$page = 1;
		}
		else
		{
			$page -= $pageNumbers;

      if ($page !== 1) $result .= '... ';
		}

		if ($last > $this->getPageCount())
		{
			$last = $this->getPageCount();
			$cut = false;
		}

		for (; $page <= $last; ++$page)
		{
			$result .= $page === $this->getPage()
				? '<span class="page-current">' . $page . '</span>'
				: '<a class="page" href="' . $href . '&amp;' . $param . '=' . $page . '">' . $page . '</a>';

			$result .= $page < $last ? ' | ' : ' ';
		}

		if ($cut && $last != $this->getPageCount())
		{
			$result .= '... ';
		}

		$result .= $this->isLinkToNextPageAvailable()
			? '<a class="page-next" href="' . $href . '&amp;' . $param . '=' . ($this->getPage() + 1) . '">Następna &rsaquo;</a> '
			#: '<span class="page-next">Next &rsaquo;</span> ';
			: '';

		$result .= $this->isLinkToLastPageAvailable()
			? '<a class="page-last" href="' . $href . '&amp;' . $param . '=' . $this->getPageCount() . '">Ostatnia &raquo;</a>'
			#: '<span class="page-last">Last &raquo;</span>';
			: '';

		return $result;
	}
}
