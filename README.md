<<<<<<< HEAD
# TableIt
Codeigniter library to easily present query results into a sortable, paginated table, plus much more

### Why?
Because repeating dozens of lines of code every time some query results need to be table-ized sucks.
This plugins allows you to simply write the following and you get a nicely, consistently drawn table

```php
$this->load->library('table');
$config = array('maxRowsPerPage' => 100, 'rowsID' => 'USER_ID');
$config['columns'] = array(
  array(
    'name' => 'Username',
    'field' => 'USERNAME',
    'sortable' => true,
  ), array(
    'html' => '::USER_LASTNAME ::USER_FIRSTNAME',
    'name' => 'Full name',
    'sortable' => 'USER_LASTNAME',
  ));
$userTable = $this->table->drawTable('myawesomemodel', 'getUsers', $params, $config);
```

### Requirements
* Codeigniter 3.x
* Twitter Bootstrap 3.x

That's it!

### How to use it
First things first, load the library as you would with any other Codeigniter library and your model

```php
$this->load->library('table');
$this->load->model('users_model');
```

Create a model and its method to retrieve the data from your database, which should accept a single parameter that will be used for all the needed query modifications

```php
public function getUsers($params = array())
{
  $this->totalRows = 0;  // Important, this is needed to count  the actual number of rows instead of just the displayed ones
  
  $this->db->select("USER_ID, USER_FIRSTNAME, USER_LASTNAME, USERNAME, count(*) over () as TOTALRESULTS")  // Important, see above, this is the Oracle implementation
    ->from('USERSTABLE');
		
  if(is_array($params['where']))
    foreach($params['where'] as $k => $v)
      if(!is_numeric($k) && !empty($v))
        $this->db->where($k, trim($v));
      else
        $this->db->where(str_replace ('"', "'", $v)); // Handle quotation marks in Oracle

  // Important for pagination
  if(!empty($params['limit']) && $params['limit']['numrows'] > 0)
	  $this->db->limit($params['limit']['numrows'], $params['limit']['offset']);
		
  // Important for sorting
  if(!empty($params['order_by']))
    $this->db->order_by($params['order_by']);

  $query = $this->db->get();
		
  $this->totalRows = $query->row()->TOTALRESULTS;
		
  return $query->result_array();
}
```

Create the table configuration array
```php
$config = array('maxRowsPerPage' => 100, 'rowsID' => 'USER_ID');
$config['columns'] = array(
  array(
    'name' => 'Username',
    'field' => 'USERNAME',
    'sortable' => true,
  ), array(
    'html' => '::USER_LASTNAME ::USER_FIRSTNAME',
    'name' => 'Full name',
    'sortable' => 'USER_LASTNAME',
  ));
```

You're done, just call the *drawTable* method and then drop your variable anywhere in the HTML
```php
$userTable = $this->table->drawTable('users_model', 'getUsers', $params, $config);
```
=======
# TableIt
Codeigniter library to easily present query results into a sortable, paginated table, plus much more

### Why?
Because repeating dozens of lines of code every time some query results need to be table-ized sucks.
This plugins allows you to simply write the following and you get a nicely, consistently drawn table

```php
$this->load->library('table');
$config = array('maxRowsPerPage' => 100, 'rowsID' => 'USER_ID');
$config['columns'] = array(
  array(
    'name' => 'Username',
    'field' => 'USERNAME',
    'sortable' => true,
  ), array(
    'html' => '::USER_LASTNAME ::USER_FIRSTNAME',
    'name' => 'Full name',
    'sortable' => 'USER_LASTNAME',
  ));
$userTable = $this->table->drawTable('myawesomemodel', 'getUsers', $params, $config);
```

### Requirements
* Codeigniter 3.x
* Twitter Bootstrap 3.x

That's it!

### How to use it
First things first, load the library as you would with any other Codeigniter library and your model

```php
$this->load->library('table');
$this->load->model('users_model');
```

Create a model and its method to retrieve the data from your database, which should accept a single parameter that will be used for all the needed query modifications

```php
public function getUsers($params = array())
{
  $this->totalRows = 0;  // Important, this is needed to count  the actual number of rows instead of just the displayed ones
  
  $this->db->select("USER_ID, USER_FIRSTNAME, USER_LASTNAME, USERNAME, count(*) over () as TOTALRESULTS")  // Important, see above, this is the Oracle implementation
    ->from('USERSTABLE');
		
  if(is_array($params['where']))
    foreach($params['where'] as $k => $v)
      if(!is_numeric($k) && !empty($v))
        $this->db->where($k, trim($v));
      else
        $this->db->where(str_replace ('"', "'", $v)); // Handle quotation marks in Oracle

  // Important for pagination
  if(!empty($params['limit']) && $params['limit']['numrows'] > 0)
	  $this->db->limit($params['limit']['numrows'], $params['limit']['offset']);
		
  // Important for sorting
  if(!empty($params['order_by']))
    $this->db->order_by($params['order_by']);

  $query = $this->db->get();
		
  $this->totalRows = $query->row()->TOTALRESULTS;
		
  return $query->result_array();
}
```

Create the table configuration array
```php
$config = array('maxRowsPerPage' => 100, 'rowsID' => 'USER_ID');
$config['columns'] = array(
  array(
    'name' => 'Username',
    'field' => 'USERNAME',
    'sortable' => true,
  ), array(
    'html' => '::USER_LASTNAME ::USER_FIRSTNAME',
    'name' => 'Full name',
    'sortable' => 'USER_LASTNAME',
  ));
```

You're done, just call the *drawTable* method and then drop your variable anywhere in the HTML
```php
$userTable = $this->table->drawTable('users_model', 'getUsers', $params, $config);
```
>>>>>>> a90ce273ffe56184bec68f0c5eec013ff26e5d18
