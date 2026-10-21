import { useEffect, useState } from 'react';

/**
 * The current time, refreshed every `intervalMs`. Reading Date.now() while rendering would make
 * renders impure; this keeps "now" in state so it only changes on the tick.
 */
export function useNow(intervalMs = 60_000): number {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), intervalMs);
    return () => clearInterval(timer);
  }, [intervalMs]);

  return now;
}
