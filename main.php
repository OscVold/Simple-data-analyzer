<!DOCTYPE html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
  <link href="standard.css" rel="stylesheet" type="text/css">
  <title></title>
</head>
<body>

  <?php
  if (empty($_REQUEST['Year']) || $_REQUEST['Year'] < 1985) {
    print('<h2>You must select a valid year and check off at least one checkbox!</h2>');
    exit;
  } else {
    $time_start = microtime(true);
    $employeeSalary = array();
    $employeeDepartment = array();
    $server = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'employees';
    $year = $_REQUEST['Year'];
    $begin = new DateTime("$year-01-01");
    $end = new DateTime("$year-12-31");
    // make interval include 31'st December and 1'st January.
    $modifiedEnd = (clone $end) -> modify('+1 day');
    $modifiedBegin = (clone $begin) -> modify('-1 day');
    $total = 0;
    $diff = 0;

    $link = mysqli_connect($server, $user, $pass, $db);
    if (!$link) {
      exit('error: could not connect to database');
    }
    $from = $begin -> format('Y/m/d');
    $to = $end -> format('Y/m/d');


    // fetch all valid salaries within time period
    $sql = "SELECT * FROM `salaries` WHERE NOT ('$from'>salaries.to_date OR '$to'<salaries.from_date)";
    $result = mysqli_query($link, $sql);
    mysqli_set_charset($link, 'utf8');
    print('<table border="1">');
    $row = mysqli_fetch_assoc($result);

    // count how many days each salary is valid
    while ($row) {
      $fromDate = new DateTime($row['from_date']);
      $toDate = new DateTime($row['to_date']);
      $salary = $row['salary'];
      $employeeSalary[$row['emp_no']] = $salary;

      // determine interval
      if ($fromDate > $begin ) {
        $start = $fromDate;
      } else {
        $start = $modifiedBegin;
      }
      if ($toDate > $end) {
        $stop = $modifiedEnd;
      } else {
        $stop = $toDate;
      }
      $diff = $start -> diff($stop);
      $days = $diff -> days;

      $total += (($salary*$days)/365);
      $row = mysqli_fetch_assoc($result);
    }

    // find department associated with salary
    $sqlDep = "SELECT * FROM `dept_emp` ORDER BY dept_emp.emp_no ASC";
    $resultDep = mysqli_query($link, $sqlDep);
    $rowDep = mysqli_fetch_assoc($resultDep);
    ksort($employeeSalary);
    while($rowDep) {
      // only interested in employees with valid salary
      if (array_key_exists($rowDep['emp_no'], $employeeSalary)) {
        $employeeDepartment[$rowDep['emp_no']] = $rowDep['dept_no'];
      }
      $rowDep = mysqli_fetch_assoc($resultDep);
    }
    ksort($employeeDepartment);

    print("<tr><td>Year: " ."$year</td></tr>");

    // fetch unique employees within time period
    $uniqueEmp = "SELECT DISTINCT emp_no FROM `salaries` WHERE NOT ('$from'>salaries.to_date OR '$to'<salaries.from_date)";
    $resultUnique = mysqli_query($link, $uniqueEmp);
    $nEmpRows = mysqli_num_rows($resultUnique);
    print("<tr><td>Salaried employees: "."$nEmpRows</td></tr>");
    $nSalaries = number_format(mysqli_num_rows($result),0,',', ' ');
    print("<tr><td>Valid salaries: "."$nSalaries</td></tr>");

    if (isset($_POST['Average'])) {
      $average =$total/$nEmpRows;
      $dailyAverage = $average/365;
      $dailyAverage = number_format($dailyAverage,2,',', ' ');
      $average= number_format($average,2,',', ' ');
      print("<tr><td>Average salary: "."$average</td></tr>");
      print("<tr><td>Daily average salary: "."$dailyAverage</td></tr>");

    }
    if (isset($_POST['Total'])) {
      $total = number_format($total,2,',', ' ');
      print("<tr><td> Total salary: " ."$total</td></tr>");
    }
  }

  mysqli_close($link);

  $SalaryDepartment = array_combine($employeeSalary,$employeeDepartment);


  function getMinFromDep_($arr,$dep) {
    return min(array_keys(array_filter($arr, function($v,$k) use ($dep) {
      return $v == $dep;
    }, ARRAY_FILTER_USE_BOTH)));
  }

  function getMaxFromDep_($arr,$dep) {
    return max(array_keys(array_filter($arr, function($v,$k) use ($dep) {
      return $v == $dep;
    }, ARRAY_FILTER_USE_BOTH)));
  }

  function getAverageFromDep_($arr,$dep) {
    $res = array_keys(array_filter($arr, function($v,$k) use ($dep) {
      return $v == $dep;
    }, ARRAY_FILTER_USE_BOTH));
    return array_sum($res)/count($res);
  }

  // create datasets
  $ColChartDataPoints =  array(
    array("y" => getAverageFromDep_($SalaryDepartment,'d001'), "label" => "Marketing" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d002'), "label" => "Finance" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d003'), "label" => "Human Resources" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d004'), "label" => "Production" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d005'), "label" => "Development" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d006'), "label" => "Quality Management" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d007'), "label" => "Sales" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d008'), "label" => "Research" ),
    array("y" => getAverageFromDep_($SalaryDepartment,'d009'), "label" => "Customer Service" )
  );

  $rangeBarDataPoints = array(
    array("label"=> "Marketing", "y"=> array(getMinFromDep_($SalaryDepartment,'d001'), getMaxFromDep_($SalaryDepartment,'d001'))),
    array("label"=> "Finance", "y"=> array(getMinFromDep_($SalaryDepartment,'d002'), getMaxFromDep_($SalaryDepartment,'d002'))),
    array("label"=> "Human Resources", "y"=> array(getMinFromDep_($SalaryDepartment,'d003'), getMaxFromDep_($SalaryDepartment,'d003'))),
    array("label"=> "Production", "y"=> array(getMinFromDep_($SalaryDepartment,'d004'), getMaxFromDep_($SalaryDepartment,'d004'))),
    array("label"=> "Development", "y"=> array(getMinFromDep_($SalaryDepartment,'d005'), getMaxFromDep_($SalaryDepartment,'d005'))),
    array("label"=> "Quality Management", "y" => array(getMinFromDep_($SalaryDepartment,'d006'), getMaxFromDep_($SalaryDepartment,'d006'))),
    array("label"=> "Sales", "y"=> array(getMinFromDep_($SalaryDepartment,'d007'), getMaxFromDep_($SalaryDepartment,'d007'))),
    array("label"=> "Research", "y"=> array(getMinFromDep_($SalaryDepartment,'d008'), getMaxFromDep_($SalaryDepartment,'d008'))),
    array("label"=> "Customer Service", "y"=> array(getMinFromDep_($SalaryDepartment,'d009'), getMaxFromDep_($SalaryDepartment,'d009')))
  );

  // stop timer and print time elapsed
  $time_stop = microtime(true);
  $time_total = $time_stop - $time_start;
  $time_total=  number_format($time_total,1,',', ' ');
  print("<tr><td> Time elapsed: " ."$time_total"." seconds</td></tr>");
  print('</table>');
  ?>


  <div id="chartContainer1" style="height: 370px; width: 100%;"></div>
  <div id="chartContainer2" style="height: 370px; width: 100%;"></div>

  <!-- Load graphs using CanvasJS -->
  <script>
  window.onload = function () {
    var chart = new CanvasJS.Chart("chartContainer1", {
      title: {
        text: "Department salary range"
      },
      axisX: {
        title: "Departments"
      },
      axisY:{
        title: "Salary range",
        logarithmic: true,
        includeZero: false
      },
      toolTip: {
        shared: true,
        reversed: true
      },
      theme: "light2",
      data: [
        {
          type: "rangeBar",
          indexLabel: "${y[#index]}",
          toolTipContent: "<b>{label}</b>: ${y[0]} to ${y[1]}",
          dataPoints: <?php echo json_encode($rangeBarDataPoints, JSON_NUMERIC_CHECK); ?>
        }
      ]
    });


    chart.render();




    var chart = new CanvasJS.Chart("chartContainer2", {
      animationEnabled: true,
      theme: "light2",
      title:{
        text: "Average department salary"
      },
      axisY: {
        title: "Salary (in USD)"
      },
      data: [{
        type: "column",
        yValueFormatString: "$#,##0.## ",
        dataPoints: <?php echo json_encode($ColChartDataPoints, JSON_NUMERIC_CHECK); ?>
      }]
    });

    chart.render();
  }
  </script>

  <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>


  </html>
