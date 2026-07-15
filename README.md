# Magento 2 RMA

Moduł obsługi autoryzacji zwrotów towarów (Return Merchandise Authorization) dla Magento 2,
kompatybilny z motywami Luma i Hyvä. Udostępnia formularze zwrotów dla klientów i gości, proces obsługi
w panelu administracyjnym, polityki zwrotów na poziomie produktu, powiadomienia oraz interfejsy
REST API i GraphQL.

## Funkcje

- zgłoszenia zwrotów z poziomu konta klienta;
- opcjonalna obsługa zwrotów dla zamówień gości;
- osobne, automatycznie wybierane widoki dla Luma i Hyvä;
- interakcje Alpine.js w Hyvä oraz RequireJS w Luma;
- tworzenie i obsługa RMA w panelu administratora w **Sprzedaż > RMA**;
- powody zwrotu, stany produktów, adresy zwrotów, polityki, rozwiązania i gotowe odpowiedzi;
- obsługa zwrotu środków, wymiany, naprawy i bonu;
- walidowany proces zmiany statusów z pełną historią;
- wątek wiadomości pomiędzy klientem a administratorem;
- załączniki oraz etykiety przesyłek zwrotnych;
- przypisywanie polityki zwrotów na poziomie produktu;
- konfigurowalne statusy zamówień, terminy zwrotów, wykluczone SKU i grupy klientów;
- powiadomienia e-mail dla klientów i administratorów;
- automatyczne anulowanie nieaktywnych zgłoszeń przez cron Magento;
- REST API dla klientów i administratorów;
- zapytania i mutacje GraphQL dla klientów;
- polskie i angielskie tłumaczenia.

## Wymagania

- Magento Open Source lub Adobe Commerce 2.4;
- PHP 8.1 lub nowszy;
- skonfigurowany i działający cron Magento;
- motyw dziedziczący po Magento Blank/Luma albo Hyvä.

Moduł nie deklaruje twardej zależności Composer od Hyvä. Aktywny motyw oraz jego rodzice są
wykrywani automatycznie, a moduł wybiera odpowiedni zestaw szablonów i skryptów.

## Instalacja

### Repozytorium Composer

Dodaj repozytorium GitHub do projektu Magento:

```bash
composer config repositories.kkkonrad-rma vcs https://github.com/kkkonrad/magento2-rma.git
composer require kkkonrad/module-rma
```

### Instalacja ręczna

Umieść moduł w katalogu:

```text
app/code/Kkkonrad/Rma
```

Następnie włącz i zainstaluj go z głównego katalogu Magento:

```bash
bin/magento module:enable Kkkonrad_Rma
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Po instalacji lub aktualizacji szablonów frontendu skompiluj zasoby sklepu zgodnie z procesem
wdrożeniowym aktywnego motywu.

## Konfiguracja

Konfiguracja modułu jest dostępna w:

**Sklepy > Konfiguracja > Kkkonrad > RMA Settings**

Najważniejsze ustawienia obejmują:

- włączenie lub wyłączenie RMA dla wybranego zakresu konfiguracji;
- domyślny termin zwrotu;
- dozwolone rozszerzenia i maksymalny rozmiar załączników;
- statusy zamówień kwalifikujące się do zwrotu;
- możliwość anulowania zgłoszenia przez klienta i obsługę zwrotów gości;
- wykluczone SKU i grupy klientów;
- regulamin zwrotów;
- nadawcę, szablony wiadomości i adres powiadomień administratora;
- termin automatycznego anulowania zgłoszeń.

Słowniki i polityki zwrotów są zarządzane w **Sprzedaż > RMA**. Przed udostępnieniem modułu
klientom sprawdź domyślne powody zwrotu oraz stany produktów. Utworzenie RMA wymaga co najmniej
jednego aktywnego powodu i jednego aktywnego stanu produktu.

## Proces zmiany statusów

Domyślny proces ma następującą postać:

```text
new -> pending_review -> approved -> item_in_transit -> item_received -> resolved -> closed
```

RMA może również zostać odrzucone lub anulowane, jeżeli pozwala na to jego aktualny status.
Wszystkie przejścia są walidowane w warstwie usług i zapisywane w historii statusów.

## API

Endpointy REST dostępne dla uwierzytelnionego klienta:

```text
GET  /rest/V1/rma/mine
GET  /rest/V1/rma/mine/:rmaId
POST /rest/V1/rma/create
POST /rest/V1/rma/:rmaId/message
```

Endpointy administratora są dostępne pod `/rest/V1/rma` i wymagają odpowiednich uprawnień ACL
modułu `Kkkonrad_Rma`.

GraphQL udostępnia następujące operacje:

```text
rmaReasons
rmaConditions
customerRmas
customerRma
createCustomerRma
addCustomerRmaMessage
```

Operacje dotyczące danych klienta wymagają prawidłowego tokenu autoryzacyjnego klienta.

## Cron

Nieaktywne zgłoszenia RMA są sprawdzane codziennie o godzinie 02:00 przez zadanie:

```text
kkkonrad_rma_auto_cancel_expired
```

Ustaw **Auto-Cancel After (Days)** na `0`, aby wyłączyć automatyczne anulowanie.

## Rozwój modułu

Uruchom testy jednostkowe z głównego katalogu Magento:

```bash
vendor/bin/phpunit app/code/Kkkonrad/Rma/Test/Unit
```

Uruchom analizę statyczną z konfiguracją modułu:

```bash
vendor/bin/phpstan analyse --configuration=app/code/Kkkonrad/Rma/phpstan.neon --no-progress
```

Przed wdrożeniem zmian schematu bazy danych wykonaj:

```bash
bin/magento setup:upgrade
bin/magento setup:db:status
```

## Uwagi produkcyjne

- Przed włączeniem modułu produkcyjnie przetestuj cały proces zwrotu i tworzenia credit memo
  z metodami płatności oraz statusami zamówień używanymi w sklepie.
- Przed przyjmowaniem zgłoszeń klientów skonfiguruj wysyłkę poczty i cron Magento.
- Traktuj konfigurację własnego CSS i JavaScript jako dane pochodzące wyłącznie od zaufanego
  administratora.
- Załączniki klientów mogą zawierać dane osobowe. Zastosuj odpowiednie zasady retencji,
  dostępu, tworzenia kopii zapasowych i ochrony prywatności dla katalogu
  `pub/media/kkkonrad/rma`.
- Przed aktualizacją modułu wykonaj kopię zapasową bazy danych oraz plików RMA.

## Licencja

Moduł jest udostępniany na licencji Open Software License 3.0 (`OSL-3.0`).
