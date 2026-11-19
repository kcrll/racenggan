<?php
class HashTable
{
    private array $buckets;
    private int $size;
    private int $count = 0;

    public function __construct(int $size = 16)
    {
        $this->size = max(1, $size);
        $this->buckets = array_fill(0, $this->size, []);
    }

    public static function hash(string $key, int $size): int
    {
        $sum = 0;
        $len = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $sum += ord($key[$i]);
        }
        return $sum % $size;
    }

    public function bucketOf(string $key): int
    {
        return self::hash($key, $this->size);
    }

    public function put(string $key, $value): void
    {
        if ($this->count / $this->size > 0.75) {
            $this->rehash();
        }
        $i = self::hash($key, $this->size);
        foreach ($this->buckets[$i] as &$pair) {
            if ($pair[0] === $key) {
                $pair[1] = $value;
                return;
            }
        }
        unset($pair);
        $this->buckets[$i][] = [$key, $value];
        $this->count++;
    }

    public function get(string $key)
    {
        $i = self::hash($key, $this->size);
        foreach ($this->buckets[$i] as $pair) {
            if ($pair[0] === $key) {
                return $pair[1];
            }
        }
        return null;
    }

    public function remove(string $key): bool
    {
        $i = self::hash($key, $this->size);
        foreach ($this->buckets[$i] as $n => $pair) {
            if ($pair[0] === $key) {
                array_splice($this->buckets[$i], $n, 1);
                $this->count--;
                return true;
            }
        }
        return false;
    }

    public function size(): int  { return $this->size; }
    public function count(): int { return $this->count; }

    private function rehash(): void
    {
        $old = $this->buckets;
        $this->size *= 2;
        $this->buckets = array_fill(0, $this->size, []);
        $this->count = 0;
        foreach ($old as $bucket) {
            foreach ($bucket as $pair) {
                $this->put($pair[0], $pair[1]);
            }
        }
    }
}

function binary_search(array $sorted, int $target, int &$steps = 0): int
{
    $low = 0;
    $high = count($sorted) - 1;
    $steps = 0;
    while ($low <= $high) {
        $steps++;
        $mid = intdiv($low + $high, 2);
        if ($sorted[$mid] === $target) {
            return $mid;
        }
        if ($sorted[$mid] < $target) {
            $low = $mid + 1;
        } else {
            $high = $mid - 1;
        }
    }
    return -1;
}

class DynamicArray
{
    private array $data;
    private int $capacity;
    private int $size = 0;
    public int $resizes = 0;

    public function __construct(int $capacity = 4)
    {
        $this->capacity = max(1, $capacity);
        $this->data = array_fill(0, $this->capacity, null);
    }

    public function append($value): void
    {
        if ($this->size === $this->capacity) {
            $bigger = array_fill(0, $this->capacity * 2, null);
            for ($i = 0; $i < $this->size; $i++) {
                $bigger[$i] = $this->data[$i];
            }
            $this->data = $bigger;
            $this->capacity *= 2;
            $this->resizes++;
        }
        $this->data[$this->size++] = $value;
    }

    public function get(int $i)     { return ($i >= 0 && $i < $this->size) ? $this->data[$i] : null; }
    public function size(): int     { return $this->size; }
    public function capacity(): int { return $this->capacity; }
    public function toArray(): array { return array_slice($this->data, 0, $this->size); }
}

function linear_search_all(array $items, string $keyword, int &$comparisons = 0): array
{
    $hits = [];
    $comparisons = 0;
    $needle = strtolower($keyword);
    foreach ($items as $i => $text) {
        $comparisons++;
        if (strpos(strtolower((string) $text), $needle) !== false) {
            $hits[] = $i;
        }
    }
    return $hits;
}

function linear_search(array $items, string $keyword): int
{
    $c = 0;
    $hits = linear_search_all($items, $keyword, $c);
    return $hits ? $hits[0] : -1;
}

function merge_sort(array $items, callable $cmp): array
{
    $n = count($items);
    if ($n <= 1) {
        return array_values($items);
    }
    $items = array_values($items);
    $mid   = intdiv($n, 2);
    $left  = merge_sort(array_slice($items, 0, $mid), $cmp);
    $right = merge_sort(array_slice($items, $mid), $cmp);

    $out = [];
    $i = $j = 0;
    while ($i < count($left) && $j < count($right)) {
        if ($cmp($left[$i], $right[$j]) <= 0) {
            $out[] = $left[$i++];
        } else {
            $out[] = $right[$j++];
        }
    }
    while ($i < count($left))  { $out[] = $left[$i++]; }
    while ($j < count($right)) { $out[] = $right[$j++]; }
    return $out;
}
