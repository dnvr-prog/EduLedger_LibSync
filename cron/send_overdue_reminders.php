<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI access only.");
}

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/mailer.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$sent = 0;
$skipped = 0;
$failed = 0;

try {
    // Find overdue books that have not been returned.
    // Send at most one reminder per borrowing record per day.
    $sql = "
        SELECT
            br.id AS borrowing_id,
            br.student_id,
            br.due_date,
            s.full_name,
            s.email,
            b.title,
            DATEDIFF(CURDATE(), br.due_date) AS days_overdue
        FROM borrowings br
        INNER JOIN students s
            ON s.student_id = br.student_id
        INNER JOIN books b
            ON b.book_id = br.book_id
        LEFT JOIN overdue_email_logs log
            ON log.borrowing_id = br.id
        WHERE br.status = 'Borrowed'
          AND br.due_date < CURDATE()
          AND br.return_date IS NULL
          AND (
              log.borrowing_id IS NULL
              OR log.last_sent_date IS NULL
              OR log.last_sent_date < CURDATE()
          )
          AND s.email IS NOT NULL
          AND TRIM(s.email) <> ''
        ORDER BY br.due_date ASC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $borrowingId = (int) $row["borrowing_id"];
        $email = trim($row["email"]);
        $name = $row["full_name"];
        $title = $row["title"];
        $dueDate = $row["due_date"];
        $daysOverdue = (int) $row["days_overdue"];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log(
                "Invalid email for borrowing ID " . $borrowingId
            );
            $failed++;
            continue;
        }

        $subject = "EduLedger: LibSync - Daily Overdue Reminder";

        $body =
            "Hello " . $name . ",\n\n" .
            "This is your daily overdue book reminder from " .
            "EduLedger: LibSync.\n\n" .
            "Book: " . $title . "\n" .
            "Due date: " . $dueDate . "\n" .
            "Days overdue: " . $daysOverdue . "\n\n" .
            "Our records show that this book has not yet been returned. " .
            "Please coordinate with the library administrator " .
            "regarding its return.\n\n" .
            "You will receive reminders while this book remains overdue.\n\n" .
            "Thank you,\nEduLedger: LibSync";

        // Do not update tracking unless email sending succeeds.
        if (!sendSystemEmail($email, $name, $subject, $body)) {
            error_log(
                "Email failed for borrowing ID " . $borrowingId
            );
            $failed++;
            continue;
        }

        // Insert first send, or update the same borrowing record
        // after a successful reminder on a later day.
        $stmt = $conn->prepare(
            "INSERT INTO overdue_email_logs
                (borrowing_id, sent_at, last_sent_date)
             VALUES (?, CURRENT_TIMESTAMP, CURDATE())
             ON DUPLICATE KEY UPDATE
                sent_at = CURRENT_TIMESTAMP,
                last_sent_date = CURDATE()"
        );

        $stmt->bind_param("i", $borrowingId);
        $stmt->execute();
        $stmt->close();

        $sent++;
    }

    echo "Daily overdue reminder run completed.\n";
    echo "Emails sent: " . $sent . "\n";
    echo "Skipped: " . $skipped . "\n";
    echo "Failed: " . $failed . "\n";

} catch (Throwable $e) {
    error_log(
        "Overdue reminder script error: " . $e->getMessage()
    );

    fwrite(STDERR, "An error occurred. Check the PHP error log.\n");
    exit(1);
}