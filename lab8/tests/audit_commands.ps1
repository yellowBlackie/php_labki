# Аудит практикумів №4–7 для Практичної №8.
# Запускати з кореня labki_php.

Write-Host "=== GET/POST usage ==="
Get-ChildItem .\lab4,.\lab5,.\lab6,.\lab7 -Recurse -Include *.php,*.js |
    Select-String -Pattern '\$_GET|\$_POST'

Write-Host "`n=== Підозріла конкатенація даних користувача в SQL ==="
Get-ChildItem .\lab4,.\lab5,.\lab6,.\lab7 -Recurse -Include *.php |
    Select-String -Pattern '\$_GET.*SELECT|\$_POST.*SELECT|SELECT.*\$_GET|SELECT.*\$_POST|INSERT.*\$_POST|UPDATE.*\$_POST'

Write-Host "`n=== CSRF ==="
Get-ChildItem .\lab4,.\lab5,.\lab6,.\lab7 -Recurse -Include *.php,*.js |
    Select-String -Pattern 'csrf_token|hash_equals|session_start'

Write-Host "`n=== Потенційне HTML-виведення ==="
Get-ChildItem .\lab4,.\lab5,.\lab6,.\lab7 -Recurse -Include *.php,*.js |
    Select-String -Pattern 'innerHTML|outerHTML|insertAdjacentHTML|htmlspecialchars|textContent'

Write-Host "`n=== Небезпечні delete через GET ==="
Get-ChildItem .\lab4,.\lab5,.\lab6,.\lab7 -Recurse -Include *.php,*.js |
    Select-String -Pattern 'delete\.php\?|href=.*delete'
