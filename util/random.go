package util

import (
	"math/rand"
	"time"
	"unsafe"
)

const (
	partitionKeyCharset = "abcdefghijklmnopqrstuvwxyz" + "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
)

type RandomStringGenerator struct {
	seededRandom *rand.Rand
	buffer       []byte
	Size         int
}

func NewRandomStringGenerator(stringSize int) *RandomStringGenerator {

	return &RandomStringGenerator{
		seededRandom: rand.New(rand.NewSource(time.Now().UnixNano())),
		buffer:       make([]byte, stringSize),
		Size:         stringSize,
	}
}

const letterBytes = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
const (
	letterIdxBits = 6                    // 6 bits to represent a letter index
	letterIdxMask = 1<<letterIdxBits - 1 // All 1-bits, as many as letterIdxBits
	letterIdxMax  = 63 / letterIdxBits   // # of letter indices fitting in 63 bits
)

func (gen *RandomStringGenerator) RandomString() string {
	var src = gen.seededRandom
	var n = gen.Size
	b := gen.buffer
	// A src.Int63() generates 63 random bits, enough for letterIdxMax characters!
	for i, cache, remain := n-1, src.Int63(), letterIdxMax; i >= 0; {
		if remain == 0 {
			cache, remain = src.Int63(), letterIdxMax
		}
		if idx := int(cache & letterIdxMask); idx < len(letterBytes) {
			b[i] = letterBytes[idx]
			i--
		}
		cache >>= letterIdxBits
		remain--
	}

	return *(*string)(unsafe.Pointer(&b))
}
