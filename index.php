<?php
    require_once 'db_connections.inc.php';
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyborg Chat</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="flex flex-row flex-wrap h-screen">
        <div class="flex-none relative z-50">
            <div class="drawer lg:drawer-open">
                <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
                <div class="drawer-content flex flex-col items-center justify-center">
                    <!-- Page content here -->
                    <label for="my-drawer-2" class="btn btn-primary fixed hover:border-gray-400 border-gray-200 hover:bg-gray-200 bg-gray-300 drawer-button lg:hidden top-4 left-6">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="#000"
                            viewBox="0 0 24 24"
                            class="inline-block h-5 w-5 stroke-current">
                            <path
                              stroke="#000"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>

                    </label>
                </div>
                <div class="drawer-side">
                    <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label>
                    <ul id="menu" class="menu border-gray-500 border-r-[1px] bg-base-200 text-base-content min-h-full w-80 p-4">
                        <!-- Sidebar content here -->
                        <?php
                            $stmt = $pdo->query("SELECT id, username FROM users");

                            while ($users = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if ($users['id'] != $_SESSION['user_id']) {
                                    echo '<li><a href="index.php?to=' . htmlspecialchars($users['id']) . '&username=' . urlencode($users['username']) . '">' . htmlspecialchars($users['username']) . '</a></li>';
                                }
                            }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex-1 flex flex-col justify-start h-full">
            <span class="text-xl font-semibold w-full text-center py-6 border-gray-500 border-b-[1px]"><?php echo (isset($_GET['username'])) ? $_GET['username'] : "Select a user"; ?></span>
            <div class="h-[calc(100vh-78px)] flex flex-col chat-wrapper w-full flex-1 p-6 items-center justify-end">
                <div id="msg-box" style="max-width: 600px;" class="chat-box flex-1 w-full h-full flex flex-col overflow-x-hidden overflow-y-scroll mb-4 relative z-10">
                    <?php 
                        if (isset($_GET['to'])) {
                            $to = $_GET['to'];
                            $user_id = $_SESSION['user_id'];
                            
                            $stmt = $pdo->prepare("
                                SELECT * FROM msg 
                                WHERE 
                                    (sender = :sender AND recipient = :recipient) 
                                    OR (sender = :recipient AND recipient = :sender)
                            ");

                            $stmt->bindParam(':sender', $user_id, PDO::PARAM_INT);
                            $stmt->bindParam(':recipient', $to, PDO::PARAM_INT);
                            $stmt->execute();
                        
                            $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                            if ($msgs) {
                                foreach ($msgs as $msg) {
                                    if ($msg['sender'] == $user_id) {
                                        echo '<div class="chat chat-end">
                                                  <div class="chat-bubble">' . htmlspecialchars($msg['msg']) . '</div>
                                              </div>';
                                    } else {
                                        echo '<div class="chat chat-start">
                                                  <div class="chat-bubble">' . htmlspecialchars($msg['msg']) . '</div>
                                              </div>';
                                    }
                                }
                                echo '<p id="h-num" style="display: none; text-align: center;">1</p>';
                            } else {
                                echo '<p style="text-align: center;">No messages found.</p>';
                                echo '<p id="h-num" style="display: none; text-align: center;">0</p>';
                            }
                        } else {
                            echo '<p style="text-align: center;">Select a user to talk to</p>';
                        }
                    ?>
                </div>
                <input id="msg-input" type="text" placeholder="Type here" class="input input-bordered w-full max-w-xs" />
            </div>
        </div>
    </div>
    <span id="from" style="display: none;"><?php echo $_SESSION['user_id']; ?></span>
    <span id="username" style="display: none;"><?php echo $_SESSION['username']; ?></span>
    <span id="to" style="display: none;"><?php echo (isset($_GET['to'])) ? $_GET['to'] : "adsi78g87ylhsfdhADSF*((Ysdf"; ?></span>
    <script src="assets/js/main.js"></script>
</body>
</html>