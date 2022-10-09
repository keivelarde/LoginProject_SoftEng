<?php 
session_start();
require "connection.php";
$email = "";
$errors = array();

//if user clicks signup button(collect data)
if(isset($_POST['signup'])){
    

    // (mysqli_real_escape_string)take form input data, put that into a variable, and inject that data into MySQL query in order to add that data to the database

    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);
    
    //if password and confirm password did not match
    if($password !== $cpassword){
        $errors['password'] = "Password not matched!";
    }

    //email  that is entered will be checked on the database
    $email_check = "SELECT * FROM usertable WHERE email = '$email'";
            // mysqli_query() function accepts a string value representing a query as one of the parameters and, executes/performs the given query on the database.
    $res = mysqli_query($con, $email_check);
    
    // if email already exist equals to true or 1
    if(mysqli_num_rows($res) > 0){
        $errors['email'] = "Email that you have entered already exist!";
    }
    // if there is no error 
    if(count($errors) === 0){
        $encpass = password_hash($password, PASSWORD_BCRYPT); // creates a new password hash
        $code = rand(999999, 111111); //generate random code 
        $status = "notverified"; //need to input otp first
        $insert_data = "INSERT INTO usertable (email, password, code, status)
                        values('$email', '$encpass', '$code', '$status')"; // insert all inputted data into the database
        
        // once idata is inputted into the database, otp will be sent on the email
        $data_check = mysqli_query($con, $insert_data);
        if($data_check){
            $subject = "Sign Up Email Verification";
            $message = "Welcome. Your verification code is $code ";
            $sender = "From: rylvel29@gmail.com";
            if(mail($email, $subject, $message, $sender)){
                $info = "We've sent a verification code to your email - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                header('location: user-otp.php');
                exit();
            }else{
                $errors['otp-error'] = "Failed while sending code!";
            }
        }else{
            $errors['db-error'] = "Failed while inserting data into database!";
        }
    }

}
    //if user click verification code submit button
    //once code is verified, database will be updated
    if(isset($_POST['check'])){
        $_SESSION['info'] = "";
        $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
        $check_code = "SELECT * FROM usertable WHERE code = $otp_code";
        $code_res = mysqli_query($con, $check_code);
        if(mysqli_num_rows($code_res) > 0){
            $fetch_data = mysqli_fetch_assoc($code_res);
            $fetch_code = $fetch_data['code'];
            $email = $fetch_data['email'];
            $code = 0;
            $status = 'verified';
            $update_otp = "UPDATE usertable SET code = $code, status = '$status' WHERE code = $fetch_code";
            $update_res = mysqli_query($con, $update_otp);
            if($update_res){
                $_SESSION['email'] = $email;
                header('location: home.php');
                exit();
            }else{
                $errors['otp-error'] = "Failed while updating code!";
            }
        }else{
            $errors['otp-error'] = "You've entered incorrect code!";
        }
    }

    //if user click login button
    if(isset($_POST['login'])){
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $password = mysqli_real_escape_string($con, $_POST['password']);
        $check_email = "SELECT * FROM usertable WHERE email = '$email'";
        $res = mysqli_query($con, $check_email);
        if(mysqli_num_rows($res) > 0){
            $fetch = mysqli_fetch_assoc($res);
            $fetch_pass = $fetch['password'];
            if(password_verify($password, $fetch_pass)){
                $_SESSION['email'] = $email;
                $status = $fetch['status'];
                if($status == 'verified'){
                  $_SESSION['email'] = $email;
                  $_SESSION['password'] = $password;
                    header('location: home.php');
                }else{
                    $info = "It's look like you haven't still verified your email - $email";
                    $_SESSION['info'] = $info;
                    header('location: user-otp.php');
                }
            }else{
                $errors['email'] = "Incorrect email or password!";
            }
        }else{
            $errors['email'] = "It's look like you're not yet a member! Click on the bottom link to signup.";
        }
    }


    
   //if login now button click
    if(isset($_POST['login-now'])){
        header('Location: login-user.php');
    }
?>