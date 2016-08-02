<?php
if (!empty($_GET['error'])) {
    header("Location: ../../#admin__ti6__calendar?error={$_GET['error']}");
} else if (!empty($_GET['code'])) {
    header("Location: ../../#admin__ti6__calendar?code={$_GET['code']}");
} else {
    header("Location: ../../#admin__ti6__calendar");
}