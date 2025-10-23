#!/bin/bash

# Script di validazione per il modulo mlab_aws_upload_assets
# Verifica che tutto sia configurato correttamente

echo "=========================================="
echo "  Validazione Modulo AWS Upload Assets"
echo "=========================================="
echo ""

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

errors=0
warnings=0

# 1. Verifica file principali
echo "1. Verifica file principali..."
files=(
    "mlab_aws_upload_assets.php"
    "composer.json"
    "config.json"
    "src/Controllers/ModuleController.php"
    "src/Services/S3Uploader.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${RED}✗${NC} $file mancante!"
        ((errors++))
    fi
done
echo ""

# 2. Verifica vendor
echo "2. Verifica dipendenze Composer..."
if [ -d "vendor" ]; then
    if [ -d "vendor/aws" ]; then
        echo -e "  ${GREEN}✓${NC} AWS SDK installato"
    else
        echo -e "  ${RED}✗${NC} AWS SDK non trovato!"
        echo "     Esegui: composer install"
        ((errors++))
    fi
else
    echo -e "  ${RED}✗${NC} Directory vendor non trovata!"
    echo "     Esegui: composer install"
    ((errors++))
fi
echo ""

# 3. Verifica sintassi PHP
echo "3. Verifica sintassi PHP..."
syntax_ok=true
for file in "${files[@]}"; do
    if [[ $file == *.php ]]; then
        if [ -f "$file" ]; then
            if ! php -l "$file" > /dev/null 2>&1; then
                echo -e "  ${RED}✗${NC} Errore di sintassi in $file"
                ((errors++))
                syntax_ok=false
            fi
        fi
    fi
done

if [ "$syntax_ok" = true ]; then
    echo -e "  ${GREEN}✓${NC} Nessun errore di sintassi"
fi
echo ""

# 4. Verifica permessi
echo "4. Verifica permessi file..."
if [ -w "." ]; then
    echo -e "  ${GREEN}✓${NC} Directory scrivibile"
else
    echo -e "  ${YELLOW}⚠${NC} Directory potrebbe non essere scrivibile"
    ((warnings++))
fi
echo ""

# 5. Verifica .gitignore
echo "5. Verifica .gitignore..."
if [ -f ".gitignore" ]; then
    if grep -q "\.env" .gitignore; then
        echo -e "  ${GREEN}✓${NC} .env escluso da git"
    else
        echo -e "  ${YELLOW}⚠${NC} .env non escluso da .gitignore"
        ((warnings++))
    fi
else
    echo -e "  ${YELLOW}⚠${NC} .gitignore non trovato"
    ((warnings++))
fi
echo ""

# 6. Riepilogo
echo "=========================================="
echo "  Riepilogo Validazione"
echo "=========================================="
if [ $errors -eq 0 ]; then
    echo -e "${GREEN}✓ Modulo validato correttamente!${NC}"
    if [ $warnings -gt 0 ]; then
        echo -e "${YELLOW}⚠ $warnings warning(s)${NC}"
    fi
    echo ""
    echo "Prossimi passi:"
    echo "1. Configura AWS (vedi INSTALL.md)"
    echo "2. Installa il modulo in PrestaShop"
    echo "3. Configura le credenziali AWS"
    exit 0
else
    echo -e "${RED}✗ $errors errore(i) trovato(i)${NC}"
    if [ $warnings -gt 0 ]; then
        echo -e "${YELLOW}⚠ $warnings warning(s)${NC}"
    fi
    echo ""
    echo "Risolvi gli errori prima di procedere."
    exit 1
fi
