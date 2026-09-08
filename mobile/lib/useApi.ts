import { useCallback, useEffect, useRef, useState } from 'react';
import { api, apiErrorMessage, isNetworkError } from './api';
import { getCached, setCached } from './offlineCache';

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

function isPaginated(value: unknown): value is Paginated<unknown> {
  return !!value && typeof value === 'object' && Array.isArray((value as Paginated<unknown>).data);
}

/**
 * Drives a searchable, paginated list backed by a Laravel ->paginate()
 * endpoint. Handles the common shapes seen across the API: a bare
 * paginator, or a paginator nested one level under a named key (e.g.
 * `{ paiements: {...} }`), selected via `resultKey`.
 */
export function usePaginatedApi<T>(endpoint: string, params: Record<string, unknown> = {}, resultKey?: string) {
  const [items, setItems] = useState<T[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const paramsRef = useRef(params);
  paramsRef.current = params;

  const fetchPage = useCallback(
    async (pageNumber: number, mode: 'initial' | 'refresh' | 'more') => {
      if (mode === 'initial') setIsLoading(true);
      if (mode === 'refresh') setIsRefreshing(true);
      if (mode === 'more') setIsLoadingMore(true);
      setError(null);

      try {
        const { data } = await api.get(endpoint, {
          params: { ...paramsRef.current, search: search || undefined, page: pageNumber },
        });
        const paginator = resultKey ? data[resultKey] : data;
        const resolved: Paginated<T> = isPaginated(paginator)
          ? paginator
          : { data: Array.isArray(paginator) ? paginator : [], current_page: 1, last_page: 1, per_page: 0, total: 0 };

        setItems((prev) => (mode === 'more' ? [...prev, ...resolved.data] : resolved.data));
        setPage(resolved.current_page);
        setLastPage(resolved.last_page);
        setTotal(resolved.total);
      } catch (err) {
        setError(apiErrorMessage(err, 'Impossible de charger la liste.'));
      } finally {
        setIsLoading(false);
        setIsRefreshing(false);
        setIsLoadingMore(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [endpoint, resultKey, search, JSON.stringify(params)]
  );

  useEffect(() => {
    fetchPage(1, 'initial');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint, resultKey, search, JSON.stringify(params)]);

  const refresh = useCallback(() => fetchPage(1, 'refresh'), [fetchPage]);
  const loadMore = useCallback(() => {
    if (!isLoadingMore && page < lastPage) fetchPage(page + 1, 'more');
  }, [fetchPage, isLoadingMore, page, lastPage]);

  return {
    items,
    total,
    search,
    setSearch,
    isLoading,
    isRefreshing,
    isLoadingMore,
    error,
    refresh,
    loadMore,
    hasMore: page < lastPage,
  };
}

/**
 * One-off GET for a single resource / form-options payload. Re-fetches
 * whenever `deps` changes.
 */
export function useApiGet<T>(endpoint: string | null, deps: unknown[] = [], options?: { cacheKey?: string }) {
  const [data, setData] = useState<T | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isFromCache, setIsFromCache] = useState(false);
  const cacheKey = options?.cacheKey;

  const load = useCallback(async () => {
    if (!endpoint) {
      setIsLoading(false);
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      const { data } = await api.get<T>(endpoint);
      setData(data);
      setIsFromCache(false);
      if (cacheKey) await setCached(cacheKey, data);
    } catch (err) {
      if (cacheKey && isNetworkError(err)) {
        const cached = await getCached<T>(cacheKey);
        if (cached) {
          setData(cached);
          setIsFromCache(true);
          setIsLoading(false);
          return;
        }
      }
      setError(apiErrorMessage(err));
    } finally {
      setIsLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint, cacheKey, ...deps]);

  useEffect(() => {
    load();
  }, [load]);

  return { data, isLoading, error, reload: load, isFromCache };
}
