<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Vector-Space Model</title>
        <style>
            .error {color: #7b241c ;}
            .message {color: #2471a3;}
            .resizedTextbox {width:500px;}
        </style>
    </head>
    <body>
        <?php
        /*
        this program takes words as an input 
        then uses Vector-Space model to retrieve relevant documents locally
        all documents should be in a folder named "documents"

        program handles 
        -Empty Files
        -Irrelevant Documents
        */
        global $inputVal, $inputErr, $msg;
        $inputVal = $inputErr = $msg = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["Query"])) {
            $files = scandir("documents");
            //print_r($files);
            unset($files[0]);
            unset($files[1]);
            $files = check_empty_files($files);
            //print_r($files);
            $Query = test_input($_POST["Query"]);
            //echo"$Query <br>";
            $TDmatrix = documents($files, $Query);
            //print_r($TDmatrix);
            //echo"<br>";
            $score = score($TDmatrix, $files);
            //print_r($score);
            //echo"<br>";
            $ranking = ranking($score);
            //print_r($ranking);
            //echo"<br>";
        }
        ?>
    <center>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <h2 style="color:#000080">Vector-Space Model</h2>    
            <table>
                <tr>
                    <td><center><input type="text" name="Query" class="resizedTextbox" value="<?php echo $inputVal; ?>" /*required=""*/></center></td>               
                <td><center><input type="image" src="search-icon.png" height="20px" name="submit"></center></td>
                </tr>
                <tr><td colspan="2"><center><span class = "error"><?php echo $inputErr; ?></span></center></td></tr>
                <tr><td colspan="2"><center><span class = "message"><?php echo $msg; ?></span></center></td></tr>
            </table>            
        </form>
    </center>
    <?php
    if (!empty($_POST["Query"]) && $inputErr != "Sorry,") {
        foreach ($ranking as $key => $value) {
            $link = "documents/" . $key;
            echo "<center><a href=$link>$key</a></center>";
            echo"<br>";
        }
    }

    function ranking($score) {
        arsort($score);
        $c = count($score);
        $score = array_diff($score, array("0"));
        if ($GLOBALS["inputErr"] != "Empty Files !") {
            if (count($score) == 0) {
                $GLOBALS["inputErr"] = "Sorry,";
                $GLOBALS["msg"] = "No Matches Found";
            } else if ($c > count($score)) {
                $GLOBALS["inputErr"] = "Irrelevant Documents !";
                $GLOBALS["msg"] = "Don't worry, we don't rank them";
            }
        } else {
            if (count($score) == 0) {
                $GLOBALS["inputErr"] = "Sorry,";
                $GLOBALS["msg"] = "No Matches Found";
            } else if ($c > count($score)) {
                $GLOBALS["inputErr"] = "Irrelevant Documents and Empty Files!";
                $GLOBALS["msg"] = "Don't worry, we don't rank Irrelevant Documents and neglect Empty Files";
            }
        }
        return $score;
    }

    function score($TDmatrix, $files) {
        foreach ($files as $value) {
            $score[$value] = 0;
        }
        //echo array_column($TDmatrix, 1);
        for ($j = 0; $j < count($files) + 1; $j++) {
            $a[$j] = 0;
            foreach ($TDmatrix as $key => $v) {
                $a[$j]+=$TDmatrix[$key][$j] * $TDmatrix[$key][$j];
            }
            $a[$j] = sqrt($a[$j]);
        }
        $j = 1;
        foreach ($files as $value) {
            foreach ($TDmatrix as $key => $v) {
                $score[$value]+=$TDmatrix[$key][0] * $TDmatrix[$key][$j];
            }
            $score[$value] = $score[$value] / max(1, ($a[0] * $a[$j]));
            $j++;
        }
        //print_r($score);
        return $score;
    }

    function test_input($data) {
        $data = str_ireplace(":", " ", $data);
        $data = str_ireplace(";", " ", $data);
        $data = str_ireplace("<", " ", $data);
        $data = str_ireplace(">", " ", $data);
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = strtolower($data);
        return $data;
    }

    function check_empty_files($files) {
        $flag = 0;
        foreach ($files as $key => $value) {
            if (filesize("documents/" . $value) == 0) {
                unset($files[$key]);
                $flag = 1;
            }
        }
        if ($flag) {
            $GLOBALS["inputErr"] = "Empty Files !";
            $GLOBALS["msg"] = "Don't worry, we neglect them";
        }
        return $files;
    }

    function documents($files, $Query) {
        $string = "";
        foreach ($files as $value) {
            $f = fopen("documents/" . $value, "r");
            $string = $string . " " . fread($f, filesize("documents/" . $value));
        }
        $string = $string . " " . $Query;
        //echo"$string <br>";
        $string = strtolower($string);
        //echo"$string <br>";
        $string = preg_split("/[\s]+/", trim($string));
        //print_r($string);
        //echo"<br>";
        $string = array_unique($string);
        //print_r($string);
        //echo"<br>";
        $i = 0;
        foreach ($string as $value) {
            $TDmatrix[$value][0] = "";
        }
        //print_r($TDmatrix);
        $freq = array_count_values(str_word_count($Query, 1));
        //print_r($freq);
        //echo"<br>";
        foreach ($TDmatrix as $key => $v) {
            if (array_key_exists($key, $freq)) {
                $TDmatrix[$key][0] = $freq[$key];
            } else {
                $TDmatrix[$key][0] = 0;
            }
        }
        //print_r($TDmatrix);
        //echo"<br>";
        $max = max(array_column($TDmatrix, 0));
        foreach ($TDmatrix as $key => $v) {
            $TDmatrix[$key][0] = ($TDmatrix[$key][0] * 1.0) / max($max, 1);
        }
        foreach ($TDmatrix as $key => $value) {
            if (array_sum($TDmatrix[$key]) == 0)
                unset($TDmatrix[$key]);
        }
        //print_r($TDmatrix);
        //echo"<br>";
        $i = 1;
        foreach ($files as $value) {
            $f = fopen("documents/" . $value, "r");
            $string = fread($f, filesize("documents/" . $value));
            $string = strtolower($string);
            $freq = array_count_values(str_word_count($string, 1));
            foreach ($TDmatrix as $key => $v) {
                if (array_key_exists($key, $freq)) {
                    $TDmatrix[$key][$i] = $freq[$key];
                } else {
                    $TDmatrix[$key][$i] = 0;
                }
            }
            //print_r($TDmatrix);
            //echo"<br>";
            $max = max(array_column($TDmatrix, $i));
            foreach ($TDmatrix as $key => $v) {
                $TDmatrix[$key][$i] = ($TDmatrix[$key][$i] * 1.0) / max($max, 1);
            }
            $i++;
        }

        foreach ($TDmatrix as $key => $v) {
            $c = 0;
            for ($j = 0; $j < count($TDmatrix[$key]); $j++) {
                if ($TDmatrix[$key][$j] > 0)
                    $c++;
            }
            for ($j = 0; $j < count($TDmatrix[$key]); $j++) {
                $TDmatrix[$key][$j] = $TDmatrix[$key][$j] * log((count($files) + 1) / max($c, 1), 2);
            }
        }
        //print_r($TDmatrix);
        return $TDmatrix;
    }
    ?>
</body>
</html>
