<<<<<<< HEAD
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Table {

	protected $columns = array();
	protected $maxRowsPerPage = 0;
	protected $numRows = 0;
	public	  $totalRows = 0;
	protected $numPages = 0;
	protected $currPage = 1;
	protected $rowsID = false;										// Query field to use as rows ID
	protected $sortTable = '';
	protected $pageTable = 0;
	protected $minimal = false;										// Displays a table with minimal features, no paging, no sorting
	protected $footer = true;										// Display results count in the footer
	protected $outHtml = '';
	protected $tableClass = 'table table-hover table-condensed';	// Table class(es)
	protected $tableId = '';										// Table ID
	protected $columnDefaults = array('class' => '',				// Each row's class(es) (ex: 'text-right')
									'sortable' => false,			// Set it to true to have the column clickable and sortable according to "field" or specify a custom field to sort by
									'hidden' => false,				// Set it to true to hide the column
									'style' => '',					// Custom css styling for the column (ex: 'text-align:center')
									'html' => '',					// Custom HTML to display instead of the field, query rows can be used by preceding them with 2 colons :: (ex: '<b>::firstname ::lastname</b>')
									'callback' => '',				// Function name that draws each column's row (es: 'drawButtons')
									'params' => '',					// Callback functions' parameters, can be a single value or an array (ex: array('name' => '::firstname', 'surname' => '::lastname'))
									'library' => '',				// Name of the parent class that defines the callback function if it's an external library (es: 'commonUtilities')
									'name' => '',					// Column's heading text / HTML
								);
	protected $limits = array('start' => 0, 'end' => -1);

	
	protected function initialize($config)
	{
		$this->tableClass = 'table table-hover table-condensed';
		$this->tableId = '';
		$this->limits = array('start' => 0, 'end' => -1);
		$this->outHtml = '';
		$this->hidden = 0;
		$this->rowsID = false;
		
		foreach($config as $key => $val)
		{
			if(isset($this->$key))
			{
				$this->$key = $val;
			}
		}
		if(count($this->columns) > 0)
		{
			for($i = 0;$i < count($this->columns); $i++)
			{
				$this->columns[$i] = array_merge($this->columnDefaults, $this->columns[$i]);
			}
		}
		
		if(isset($_POST['_prevSortTable']))
			$this->sortTable = trim($_POST['_prevSortTable']);
		if(isset($_POST['_sortTable']) && trim($_POST['_sortTable'] != ''))
		{
			if($this->sortTable == trim($_POST['_sortTable']))
			{
				$this->sortTable = $this->sortTable.' DESC';
			}
			elseif($this->sortTable == trim($_POST['_sortTable']).' DESC')
			{
				$this->sortTable = str_replace('DESC', '', $this->sortTable);
			}
			else 
			{
				$this->sortTable = trim($_POST['_sortTable']);
			}
		}

		if(isset($_POST['_hidden']) && is_numeric($_POST['_hidden']))
			$this->currPage = $_POST['_hidden'];
		
		if($this->maxRowsPerPage != 0)
		{
			$this->limits['start'] = (($this->currPage -1) * $this->maxRowsPerPage);
			$this->limits['end'] = $this->limits['start'] + $this->maxRowsPerPage;
		}
		
		if($this->minimal)
		{
			$this->maxRowsPerPage = 0;
			$this->numPages = 0;
		}
		
		
	}	
	
	/**
	* Draw the table from a model's query
	*
	* @param string $model 		The model name
	* @param string $function 	The model's function name, which contains the query used to draw the table
	* @param array	$params 	Params used to filter/sort/group by the query
	* @param array  $config 	Configuration params to initialize the table library
	*
	*/
	public function drawTable($model, $function, $params = null, $config = null)
	{			
		$this->ci =& get_instance();
		
		if(count($config > 0))
			$this->initialize($config);		

		if($this->maxRowsPerPage > 0)
		{
			$params['limit']['numrows'] = $this->maxRowsPerPage;
			$params['limit']['offset'] = $this->limits['start']+1;
		}

		if(!empty($this->sortTable))
			$params['order_by'] = $this->sortTable;

		$results = $this->ci->$model->$function($params);
				
		if(!is_array($results) || count($results) == 0)
			return false;
		
		$this->totalRows = $this->ci->$model->totalRows;
		
		if($this->maxRowsPerPage > 0)
			$this->numPages = ceil($this->totalRows / $this->maxRowsPerPage);
		
		if($this->limits['end'] > $this->totalRows)
			$this->limits['end'] = $this->totalRows;

		$this->tableHtml($results);
		
		return $this->outHtml;
	}

	/**
	* Draw the table from an array
	*
	* @param array 	$array 		The values array
	* @param array  $config 	Configuration params to initialize the table library
	*
	*/
	public function drawTableFromArray($array, $config = null)
	{
		$this->ci =& get_instance();
		
		if(count($config > 0))
			$this->initialize($config);

		if(!is_array($array))
			trigger_error('Invalid array',E_USER_ERROR);
		
		if(count($array) == 0)
			return false;
		
		$this->totalRows = count($array);
		
		if($this->maxRowsPerPage > 0)
			$this->numPages = ceil($this->totalRows / $this->maxRowsPerPage);
		
		if($this->limits['end'] > $this->totalRows)
			$this->limits['end'] = $this->totalRows;
		
		if(!empty($this->sortTable))
		{
			$sortDir = SORT_ASC;
			$sort = $this->sortTable;
			if(strpos($sort, ' DESC') == strlen($sort)-5)
			{
				$sort = substr($sort, 0, strlen($sort)-5);
				$sortDir = SORT_DESC;
			}
			$array = $this->array_orderby($array, trim($sort), $sortDir);
		}
		
		if(!$this->minimal)
			$slicedArray = array_slice ($array, $this->limits['start'], $this->maxRowsPerPage);
		else
			$slicedArray = $array;
		
		$this->tableHtml($slicedArray);

		return $this->outHtml;
	}
	
	protected function array_orderby()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
				}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}

	protected function tableHtml($array)
	{	
		if(!$this->minimal)
			$this->outHtml = '<form method="post">';
		else
			$this->outHtml = '';
		
		$this->outHtml .= '<table id="'.$this->tableId.'" class="'.$this->tableClass.'">
				<thead>
					<tr>';

		$this->row = $array[0];			

		if(count($this->columns) == 0)
		{
			foreach($this->row as $key=>$val)
				if(!in_array($key, array('RNUM','TOTALRESULTS')))
					$this->outHtml .= '<th>'.$this->sanitizeCell($key).'</th>';
		}
		else
		{
			foreach($this->columns as $column)
			{
				$fieldOrder = ($column['sortable'] === false ? false : ($column['sortable'] !== true ? $column['sortable'] : $column['field']));

				$this->outHtml .= '<th style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
							.(($fieldOrder !== false && $this->minimal !== true) ? '<button type="submit" class="sortTable" name="_sortTable" value="'.($fieldOrder).'">
														<span class="text-primary">'.$column['name'].'</span>
														'.($this->sortTable == $fieldOrder.' DESC' ? '<span class="caret text-right"></span>':'').'
														'.(trim($this->sortTable) == $fieldOrder ? '<span class="caret-up text-right"></span>':'').'
													</button>' : $column['name']).
					'</th>';
			}
		}

		$this->outHtml .= '		</tr>
				</thead>
				<tbody>';

		$counterRows = 0;

		$this->drawRow($this->row);
		
		if(is_array($array))
		{
			for($i=1;$i<count($array);$i++)
			{
				if($counterRows <= $this->maxRowsPerPage || $this->maxRowsPerPage == 0)
				{
					$this->row = $array[$i];
					$this->drawRow($this->row);
					$counterRows++;
				}
			}	
		}
	

		$this->outHtml .= '</tbody></table>';
		if(!$this->minimal)
		{
			foreach($_POST as $key => $val)
			{
				if(substr($key,0,1) != '_')
				{
					if(is_array($val))
					{
						foreach($val as $v)
							$this->outHtml .= '<input type="hidden" name="'.$key.'[]" value="'.$v.'"/>';
					}
					else
						$this->outHtml .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
				}
			}
			$this->outHtml .= '<input type="hidden" name="_prevSortTable" value="'.$this->sortTable.'"/>';
		}
		
		if($this->footer)
		{
			$this->outHtml .= '<div class="table-footer"><div class="row">';
			$this->outHtml .= '<div class="col-sm-6 text-muted">
									Showing rows '.($this->limits['start'] + 1).' - '.$this->limits['end'].' of '.$this->totalRows.'
								</div>';

			if($this->numPages > 1)
			{
				$this->outHtml .= '<div class="col-sm-6 text-right">';
				$this->outHtml .= $this->drawPaginator();
				$this->outHtml .= '</div>';
			}
			$this->outHtml .= '</div></div>';
		}
		
		if(!$this->minimal)
			$this->outHtml .= '</form>';
	}
	
	/**
	* Draws each row
	*
	*/
	protected function drawRow($row)
	{
	   $this->outHtml .= '<tr '.($this->rowsID ? 'id="'.$row[$this->rowsID].'"' : '').'>';
	   if(count($this->columns) == 0)
	   {
		   foreach($row as $key=>$val)
		   {
			   if(!in_array($key, array('RNUM','TOTALRESULTS')))
				   $this->outHtml .= '<td>'.$this->sanitizeCell($val).'</td>';
		   }
	   }
	   else
	   {
		   foreach($this->columns as $column)
		   {
				$columnClass = '';
				if(is_array($column['class']))
				{
					if(is_array($column['class'][0]))
					{
						foreach($column['class'] as $k => $v)
						{
							$columnClass .= $v['classes'][$row[$v['field']]]." ";
						}
					}
					else
						$columnClass = $column['class']['classes'][$row[$column['class']['field']]];
				}
				else
					$columnClass =  $column['class'];

			   if(!empty($column['callback']))
				{
					if(is_array($column['params']))
					{
						$params = array();
						foreach($column['params'] as $k => $v)
							$params[$k] = @preg_replace('~\:\:(\w+)~e', '$this->sanitizeCell($row[strtoupper($1)])', $v);
					}
					else
						$params = @preg_replace('~\:\:(\w+)~e', '$this->sanitizeCell($row[strtoupper($1)])', $column['params']);
					
					if(!empty($column['library']))
					{
						$this->ci->load->library($column['library']);
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$this->ci->$column['library']->$column['callback']($params)
										.'</td>';
					}
					elseif(function_exists($column['callback']))
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$column['callback']($params)
										.'</td>';
					else
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$this->ci->$column['callback']($params)
										.'</td>';
				}
				elseif(trim($column['html']) != '')
				{
					//turns ::var into $row['var']
					$html = @preg_replace('~\:\:(\w+)~e', '$row[strtoupper($1)]', $column['html']);
					$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'.$html.'</td>';
				}
				else
					$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'.$this->sanitizeCell($row[strtoupper($column['field'])]).'</td>';
		   }
	   }
	   $this->outHtml .= '</tr>';		
	}
	
	protected function sanitizeCell($item)
	{
		return (trim($item) != '' ? htmlentities(trim($item),ENT_QUOTES) : '&nbsp;');
	}
	
	protected function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	protected function drawPaginator()
	{
		$maxPaginator = 5;
		$pag = '<ul class="pagination">';
		if($this->currPage == 1)
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage - 1).'" disabled="disabled">&laquo;</button></li>';
		else
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage - 1).'">&laquo;</button></li>';
		
		$pag .= '<li '.($this->currPage == 1 ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="1">1</button></li>';
		
		$start = $this->currPage - floor($maxPaginator/2) > 2 ? $this->currPage - floor($maxPaginator/2) : 2;
		$end = $start + ($maxPaginator -1) < $this->numPages -1 ? $start + $maxPaginator -1  : $this->numPages -1;
		
		
		if($start > 2)
			$pag .= '<li><span class="ellips">...</span></li>';
		
		for($i = $start; $i <= $end; $i++)
			$pag .= '<li '.($this->currPage == ($i) ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($i).'">'.$i.'</button></li>';
		
		if($end < $this->numPages -1)
			$pag .= '<li><span class="ellips">...</span></li>';
		
		$pag .= '<li '.($this->currPage == $this->numPages ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->numPages).'">'.($this->numPages).'</button></li>';

		if($this->currPage == $this->numPages -1)
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage + 1).'" disabled="disabled">&raquo;</button></li>';
		else
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage + 1).'">&raquo;</button></li>';
		
		$pag .= '</ul>';
		
		return $pag;
	}

}
=======
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Table {

	protected $columns = array();
	protected $maxRowsPerPage = 0;
	protected $numRows = 0;
	public	  $totalRows = 0;
	protected $numPages = 0;
	protected $currPage = 1;
	protected $rowsID = false;										// Query field to use as rows ID
	protected $sortTable = '';
	protected $pageTable = 0;
	protected $minimal = false;										// Displays a table with minimal features, no paging, no sorting
	protected $footer = true;										// Display results count in the footer
	protected $outHtml = '';
	protected $tableClass = 'table table-hover table-condensed';	// Table class(es)
	protected $tableId = '';										// Table ID
	protected $columnDefaults = array('class' => '',				// Each row's class(es) (ex: 'text-right')
									'sortable' => false,			// Set it to true to have the column clickable and sortable according to "field" or specify a custom field to sort by
									'hidden' => false,				// Set it to true to hide the column
									'style' => '',					// Custom css styling for the column (ex: 'text-align:center')
									'html' => '',					// Custom HTML to display instead of the field, query rows can be used by preceding them with 2 colons :: (ex: '<b>::firstname ::lastname</b>')
									'callback' => '',				// Function name that draws each column's row (es: 'drawButtons')
									'params' => '',					// Callback functions' parameters, can be a single value or an array (ex: array('name' => '::firstname', 'surname' => '::lastname'))
									'library' => '',				// Name of the parent class that defines the callback function if it's an external library (es: 'commonUtilities')
									'name' => '',					// Column's heading text / HTML
								);
	protected $limits = array('start' => 0, 'end' => -1);

	
	protected function initialize($config)
	{
		$this->tableClass = 'table table-hover table-condensed';
		$this->tableId = '';
		$this->limits = array('start' => 0, 'end' => -1);
		$this->outHtml = '';
		$this->hidden = 0;
		$this->rowsID = false;
		
		foreach($config as $key => $val)
		{
			if(isset($this->$key))
			{
				$this->$key = $val;
			}
		}
		if(count($this->columns) > 0)
		{
			for($i = 0;$i < count($this->columns); $i++)
			{
				$this->columns[$i] = array_merge($this->columnDefaults, $this->columns[$i]);
			}
		}
		
		if(isset($_POST['_prevSortTable']))
			$this->sortTable = trim($_POST['_prevSortTable']);
		if(isset($_POST['_sortTable']) && trim($_POST['_sortTable'] != ''))
		{
			if($this->sortTable == trim($_POST['_sortTable']))
			{
				$this->sortTable = $this->sortTable.' DESC';
			}
			elseif($this->sortTable == trim($_POST['_sortTable']).' DESC')
			{
				$this->sortTable = str_replace('DESC', '', $this->sortTable);
			}
			else 
			{
				$this->sortTable = trim($_POST['_sortTable']);
			}
		}

		if(isset($_POST['_hidden']) && is_numeric($_POST['_hidden']))
			$this->currPage = $_POST['_hidden'];
		
		if($this->maxRowsPerPage != 0)
		{
			$this->limits['start'] = (($this->currPage -1) * $this->maxRowsPerPage);
			$this->limits['end'] = $this->limits['start'] + $this->maxRowsPerPage;
		}
		
		if($this->minimal)
		{
			$this->maxRowsPerPage = 0;
			$this->numPages = 0;
		}
		
		
	}	
	
	/**
	* Draw the table from a model's query
	*
	* @param string $model 		The model name
	* @param string $function 	The model's function name, which contains the query used to draw the table
	* @param array	$params 	Params used to filter/sort/group by the query
	* @param array  $config 	Configuration params to initialize the table library
	*
	*/
	public function drawTable($model, $function, $params = null, $config = null)
	{			
		$this->ci =& get_instance();
		
		if(count($config > 0))
			$this->initialize($config);		

		if($this->maxRowsPerPage > 0)
		{
			$params['limit']['numrows'] = $this->maxRowsPerPage;
			$params['limit']['offset'] = $this->limits['start']+1;
		}

		if(!empty($this->sortTable))
			$params['order_by'] = $this->sortTable;

		$results = $this->ci->$model->$function($params);
				
		if(!is_array($results) || count($results) == 0)
			return false;
		
		$this->totalRows = $this->ci->$model->totalRows;
		
		if($this->maxRowsPerPage > 0)
			$this->numPages = ceil($this->totalRows / $this->maxRowsPerPage);
		
		if($this->limits['end'] > $this->totalRows)
			$this->limits['end'] = $this->totalRows;

		$this->tableHtml($results);
		
		return $this->outHtml;
	}

	/**
	* Draw the table from an array
	*
	* @param array 	$array 		The values array
	* @param array  $config 	Configuration params to initialize the table library
	*
	*/
	public function drawTableFromArray($array, $config = null)
	{
		$this->ci =& get_instance();
		
		if(count($config > 0))
			$this->initialize($config);

		if(!is_array($array))
			trigger_error('Invalid array',E_USER_ERROR);
		
		if(count($array) == 0)
			return false;
		
		$this->totalRows = count($array);
		
		if($this->maxRowsPerPage > 0)
			$this->numPages = ceil($this->totalRows / $this->maxRowsPerPage);
		
		if($this->limits['end'] > $this->totalRows)
			$this->limits['end'] = $this->totalRows;
		
		if(!empty($this->sortTable))
		{
			$sortDir = SORT_ASC;
			$sort = $this->sortTable;
			if(strpos($sort, ' DESC') == strlen($sort)-5)
			{
				$sort = substr($sort, 0, strlen($sort)-5);
				$sortDir = SORT_DESC;
			}
			$array = $this->array_orderby($array, trim($sort), $sortDir);
		}
		
		if(!$this->minimal)
			$slicedArray = array_slice ($array, $this->limits['start'], $this->maxRowsPerPage);
		else
			$slicedArray = $array;
		
		$this->tableHtml($slicedArray);

		return $this->outHtml;
	}
	
	protected function array_orderby()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
				}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}

	protected function tableHtml($array)
	{	
		if(!$this->minimal)
			$this->outHtml = '<form method="post">';
		else
			$this->outHtml = '';
		
		$this->outHtml .= '<table id="'.$this->tableId.'" class="'.$this->tableClass.'">
				<thead>
					<tr>';

		$this->row = $array[0];			

		if(count($this->columns) == 0)
		{
			foreach($this->row as $key=>$val)
				if(!in_array($key, array('RNUM','TOTALRESULTS')))
					$this->outHtml .= '<th>'.$this->sanitizeCell($key).'</th>';
		}
		else
		{
			foreach($this->columns as $column)
			{
				$fieldOrder = ($column['sortable'] === false ? false : ($column['sortable'] !== true ? $column['sortable'] : $column['field']));

				$this->outHtml .= '<th style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
							.(($fieldOrder !== false && $this->minimal !== true) ? '<button type="submit" class="sortTable" name="_sortTable" value="'.($fieldOrder).'">
														<span class="text-primary">'.$column['name'].'</span>
														'.($this->sortTable == $fieldOrder.' DESC' ? '<span class="caret text-right"></span>':'').'
														'.(trim($this->sortTable) == $fieldOrder ? '<span class="caret-up text-right"></span>':'').'
													</button>' : $column['name']).
					'</th>';
			}
		}

		$this->outHtml .= '		</tr>
				</thead>
				<tbody>';

		$counterRows = 0;

		$this->drawRow($this->row);
		
		if(is_array($array))
		{
			for($i=1;$i<count($array);$i++)
			{
				if($counterRows <= $this->maxRowsPerPage || $this->maxRowsPerPage == 0)
				{
					$this->row = $array[$i];
					$this->drawRow($this->row);
					$counterRows++;
				}
			}	
		}
	

		$this->outHtml .= '</tbody></table>';
		if(!$this->minimal)
		{
			foreach($_POST as $key => $val)
			{
				if(substr($key,0,1) != '_')
				{
					if(is_array($val))
					{
						foreach($val as $v)
							$this->outHtml .= '<input type="hidden" name="'.$key.'[]" value="'.$v.'"/>';
					}
					else
						$this->outHtml .= '<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
				}
			}
			$this->outHtml .= '<input type="hidden" name="_prevSortTable" value="'.$this->sortTable.'"/>';
		}
		
		if($this->footer)
		{
			$this->outHtml .= '<div class="table-footer"><div class="row">';
			$this->outHtml .= '<div class="col-sm-6 text-muted">
									Showing rows '.($this->limits['start'] + 1).' - '.$this->limits['end'].' of '.$this->totalRows.'
								</div>';

			if($this->numPages > 1)
			{
				$this->outHtml .= '<div class="col-sm-6 text-right">';
				$this->outHtml .= $this->drawPaginator();
				$this->outHtml .= '</div>';
			}
			$this->outHtml .= '</div></div>';
		}
		
		if(!$this->minimal)
			$this->outHtml .= '</form>';
	}
	
	/**
	* Draws each row
	*
	*/
	protected function drawRow($row)
	{
	   $this->outHtml .= '<tr '.($this->rowsID ? 'id="'.$row[$this->rowsID].'"' : '').'>';
	   if(count($this->columns) == 0)
	   {
		   foreach($row as $key=>$val)
		   {
			   if(!in_array($key, array('RNUM','TOTALRESULTS')))
				   $this->outHtml .= '<td>'.$this->sanitizeCell($val).'</td>';
		   }
	   }
	   else
	   {
		   foreach($this->columns as $column)
		   {
				$columnClass = '';
				if(is_array($column['class']))
				{
					if(is_array($column['class'][0]))
					{
						foreach($column['class'] as $k => $v)
						{
							$columnClass .= $v['classes'][$row[$v['field']]]." ";
						}
					}
					else
						$columnClass = $column['class']['classes'][$row[$column['class']['field']]];
				}
				else
					$columnClass =  $column['class'];

			   if(!empty($column['callback']))
				{
					if(is_array($column['params']))
					{
						$params = array();
						foreach($column['params'] as $k => $v)
							$params[$k] = @preg_replace('~\:\:(\w+)~e', '$this->sanitizeCell($row[strtoupper($1)])', $v);
					}
					else
						$params = @preg_replace('~\:\:(\w+)~e', '$this->sanitizeCell($row[strtoupper($1)])', $column['params']);
					
					if(!empty($column['library']))
					{
						$this->ci->load->library($column['library']);
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$this->ci->$column['library']->$column['callback']($params)
										.'</td>';
					}
					elseif(function_exists($column['callback']))
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$column['callback']($params)
										.'</td>';
					else
						$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'
											.$this->ci->$column['callback']($params)
										.'</td>';
				}
				elseif(trim($column['html']) != '')
				{
					//turns ::var into $row['var']
					$html = @preg_replace('~\:\:(\w+)~e', '$row[strtoupper($1)]', $column['html']);
					$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'.$html.'</td>';
				}
				else
					$this->outHtml .= '<td class="'.$columnClass.'" style="'.trim(($column['hidden'] ? 'display:none;' : '').' '.$column['style']).'">'.$this->sanitizeCell($row[strtoupper($column['field'])]).'</td>';
		   }
	   }
	   $this->outHtml .= '</tr>';		
	}
	
	protected function sanitizeCell($item)
	{
		return (trim($item) != '' ? htmlentities(trim($item),ENT_QUOTES) : '&nbsp;');
	}
	
	protected function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	protected function drawPaginator()
	{
		$maxPaginator = 5;
		$pag = '<ul class="pagination">';
		if($this->currPage == 1)
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage - 1).'" disabled="disabled">&laquo;</button></li>';
		else
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage - 1).'">&laquo;</button></li>';
		
		$pag .= '<li '.($this->currPage == 1 ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="1">1</button></li>';
		
		$start = $this->currPage - floor($maxPaginator/2) > 2 ? $this->currPage - floor($maxPaginator/2) : 2;
		$end = $start + ($maxPaginator -1) < $this->numPages -1 ? $start + $maxPaginator -1  : $this->numPages -1;
		
		
		if($start > 2)
			$pag .= '<li><span class="ellips">...</span></li>';
		
		for($i = $start; $i <= $end; $i++)
			$pag .= '<li '.($this->currPage == ($i) ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($i).'">'.$i.'</button></li>';
		
		if($end < $this->numPages -1)
			$pag .= '<li><span class="ellips">...</span></li>';
		
		$pag .= '<li '.($this->currPage == $this->numPages ? 'class="active"' : '').'><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->numPages).'">'.($this->numPages).'</button></li>';

		if($this->currPage == $this->numPages -1)
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage + 1).'" disabled="disabled">&raquo;</button></li>';
		else
			$pag .= '<li><button type="submit" class="btn btn-sm btn-default" name="_hidden" value="'.($this->currPage + 1).'">&raquo;</button></li>';
		
		$pag .= '</ul>';
		
		return $pag;
	}

}
>>>>>>> a90ce273ffe56184bec68f0c5eec013ff26e5d18
